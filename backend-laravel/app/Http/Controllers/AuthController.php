<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show Login Form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isSuperAdmin() 
                ? redirect()->route('dashboard') 
                : redirect()->route('vouchers.index');
        }
        return view('auth.login');
    }

    /**
     * Handle Login Attempt
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $loginInput = trim($request->input('username'));
        $password   = $request->input('password');
        $remember   = $request->boolean('remember');

        // Determine if login input is email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $loginInput,
            'password'  => $password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            if ($user->isOperator()) {
                return redirect()->route('vouchers.index')->with('success', "Welcome back, {$user->name}! Operator portal active.");
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('username'))
            ->with('error', 'Invalid username or password. Please try again.');
    }

    /**
     * Handle Logout
     */
    public function logout(Request $request)
    {
        $name = Auth::user()->name ?? 'User';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', "Goodbye, {$name}! You have been logged out.");
    }
}
