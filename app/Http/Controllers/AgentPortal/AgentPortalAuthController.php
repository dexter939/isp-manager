<?php

declare(strict_types=1);

namespace App\Http\Controllers\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\AgentPortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AgentPortalAuthController extends Controller
{
    public function showLogin()
    {
        return view('agent-portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $agent = AgentPortalUser::where('portal_email', $credentials['email'])
            ->whereNotNull('portal_password')
            ->where('status', 'active')
            ->first();

        if (!$agent || !Hash::check($credentials['password'], $agent->portal_password)) {
            throw ValidationException::withMessages([
                'email' => 'Credenziali non valide o accesso non abilitato.',
            ]);
        }

        Auth::guard('agent')->login($agent, $request->boolean('remember'));

        $agent->update(['portal_last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->route('agent-portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent-portal.login');
    }
}
