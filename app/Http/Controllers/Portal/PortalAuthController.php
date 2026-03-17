<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PortalAuthController extends Controller
{
    public function showLogin()
    {
        return view('portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Cerca il cliente con accesso portale abilitato
        $customer = \App\Models\CustomerPortalUser::where('email', $credentials['email'])
            ->whereNotNull('portal_password')
            ->whereNull('deleted_at')
            ->first();

        if (!$customer || !Hash::check($credentials['password'], $customer->portal_password)) {
            throw ValidationException::withMessages([
                'email' => 'Credenziali non valide.',
            ]);
        }

        Auth::guard('portal')->login($customer, $request->boolean('remember'));

        $customer->update(['portal_last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
