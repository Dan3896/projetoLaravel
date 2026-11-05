<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Limite por e-mail + IP, para não bloquear todo mundo atrás do mesmo IP.
        $chave = Str::transliterate(Str::lower($request->input('email'))) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            $segundos = RateLimiter::availableIn($chave);

            return back()->withErrors([
                'email' => "Muitas tentativas de login. Tente novamente em {$segundos} segundos.",
            ])->onlyInput('email');
        }

        // Usuário desativado no admin não entra, mesmo com a senha certa.
        if (Auth::attempt($credentials + ['ativo' => true])) {
            RateLimiter::clear($chave);
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        RateLimiter::hit($chave, 60);

        return back()->withErrors([
            'email' => 'As credenciais não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
