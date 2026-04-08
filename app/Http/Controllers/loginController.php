<?php

namespace App\Http\Controllers;

use App\Http\Requests\loginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class loginController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-user-estado', ['only' => ['login']]);
    }

    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('panel');
        }
        return view('auth.login');
    }

    public function login(loginRequest $request): RedirectResponse
    {

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return redirect()
                ->route('login.index')
                ->withErrors('Credenciales incorrectas')
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return redirect()->route('panel')->with('login', 'Bienvenido ' . $user->name);
    }
}
