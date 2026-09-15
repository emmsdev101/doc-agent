<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterOrganizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request, RegisterOrganizationAction $action): RedirectResponse
    {
        $user = $action->execute($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
