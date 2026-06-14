<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Auth\AuthenticationException;

class SingleSessionEnforcement extends AuthenticateSession
{
    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function logout($request)
    {
        $this->guard()->logoutCurrentDevice();

        $request->session()->flush();

        // After flushing, flash the message to display the SweetAlert on the login screen
        $request->session()->flash('session_expired', 'Your account was logged in from another device. For security reasons, your session here has been terminated.');

        throw new AuthenticationException(
            'Unauthenticated.', [$this->auth->getDefaultDriver()], $this->redirectTo($request)
        );
    }
}
