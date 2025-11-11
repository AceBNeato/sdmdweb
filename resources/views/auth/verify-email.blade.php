<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification - {{ config('app.name', 'SDMD Equipment Management') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <!-- Logo -->
            <div class="text-center mb-6">
                <img src="{{ asset('images/SDMDlogo.png') }}" alt="SDMD Logo" class="mx-auto h-16 w-auto">
                <h2 class="mt-4 text-2xl font-bold text-gray-900">SDMD Equipment Management</h2>
            </div>

            <div class="mb-4 text-sm text-gray-600">
                {{ __('Before proceeding, please check your email for a verification link.') }}
            </div>

            @if (session('resent'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            <div class="mb-4 text-sm text-gray-600">
                {{ __('If you did not receive the email, we will gladly send you another.') }}
            </div>

            <form method="POST" action="{{ route('email.verification.resend') }}">
                @csrf

                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('logout') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    {{ __('Logout') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
