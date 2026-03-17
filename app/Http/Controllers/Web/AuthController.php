<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Credenziali non valide.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->withInput();
    }

    public function resetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->withInput($request->except('password'));
    }

    public function profile()
    {
        $user     = auth()->user();
        $tenantId = $user->tenant_id;

        // Roles (stored as JSON array on users table)
        $roles = is_array($user->roles)
            ? $user->roles
            : json_decode($user->roles ?? '[]', true);

        // Recent activity: last 10 tickets assigned or created by this user
        $recentTickets = DB::table('tickets as t')
            ->where(function ($q) use ($user) {
                $q->where('t.assigned_to', $user->id)
                  ->orWhere('t.created_by', $user->id);
            })
            ->where('t.tenant_id', $tenantId)
            ->leftJoin('customers as cu', 'cu.id', '=', 't.customer_id')
            ->select('t.id', 't.ticket_number', 't.title', 't.status', 't.priority', 't.opened_at')
            ->orderByDesc('t.opened_at')
            ->limit(5)
            ->get();

        // Stats
        $stats = [
            'tickets_assigned' => DB::table('tickets')
                ->where('tenant_id', $tenantId)
                ->where('assigned_to', $user->id)
                ->count(),
            'tickets_resolved' => DB::table('tickets')
                ->where('tenant_id', $tenantId)
                ->where('assigned_to', $user->id)
                ->whereNotNull('resolved_at')
                ->count(),
            'tickets_open'     => DB::table('tickets')
                ->where('tenant_id', $tenantId)
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
        ];

        return view('auth.profile', compact('user', 'roles', 'recentTickets', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$user->id}",
        ]);

        DB::table('users')->where('id', $user->id)->update([
            'name'       => $request->input('name'),
            'email'      => $request->input('email'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Profilo aggiornato.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'La password attuale non è corretta.'])
                         ->withFragment('password');
        }

        DB::table('users')->where('id', $user->id)->update([
            'password'   => Hash::make($request->input('password')),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Password aggiornata.')->withFragment('password');
    }
}
