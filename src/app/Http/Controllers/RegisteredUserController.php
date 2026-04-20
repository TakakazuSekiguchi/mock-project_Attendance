<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\RegisterRequest;

class RegisteredUserController
{
    public function store(RegisterRequest $request, CreateNewUser $creator){
        event(new Registered($user = $creator->create($request->all())));
        session()->put('unauthenticated_user', $user);
        return redirect()->route('verification.notice');
    }
}