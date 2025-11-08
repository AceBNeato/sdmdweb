@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6">
                <div class="text-blue-600 text-2xl mr-3">
                    <i class="fas fa-cogs"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Session Timeout Setting -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="text-yellow-500 text-xl mr-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Session Timeout</h3>
                    </div>

                    <p class="text-gray-600 mb-4">
                        Configure how long users remain logged in when inactive. Users will be automatically logged out after this period of inactivity.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="session_timeout_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                Timeout Duration (minutes)
                            </label>
                            <input
                                type="number"
                                id="session_timeout_minutes"
                                name="session_timeout_minutes"
                                value="{{ $settings['session_timeout_minutes'] }}"
                                min="1"
                                max="60"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-1">Range: 1-60 minutes</p>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 mb-2">Current Setting</h4>
                            <p class="text-blue-800">
                                <span class="font-bold">{{ $settings['session_timeout_minutes'] }}</span> minutes
                            </p>
                            <p class="text-sm text-blue-600 mt-1">
                                Users will be logged out after {{ $settings['session_timeout_minutes'] }} minutes of inactivity
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Session Lockout Setting -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="text-blue-500 text-xl mr-3">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Session Lockout</h3>
                    </div>

                    <p class="text-gray-600 mb-4">
                        Configure when the screen locks due to inactivity. Users must enter their password to unlock and continue working.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="session_lockout_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                                Lockout Duration (minutes)
                            </label>
                            <input
                                type="number"
                                id="session_lockout_minutes"
                                name="session_lockout_minutes"
                                value="{{ $settings['session_lockout_minutes'] ?? $settings['session_timeout_minutes'] }}"
                                min="1"
                                max="60"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-1">Range: 1-60 minutes</p>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <h4 class="font-semibold text-green-900 mb-2">Current Setting</h4>
                            <p class="text-green-800">
                                <span class="font-bold">{{ $settings['session_lockout_minutes'] ?? $settings['session_timeout_minutes'] }}</span> minutes
                            </p>
                            <p class="text-sm text-green-600 mt-1">
                                Screen will lock after {{ $settings['session_lockout_minutes'] ?? $settings['session_timeout_minutes'] }} minutes of inactivity
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Security Information -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="text-yellow-600 text-lg mr-3 mt-0.5">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-yellow-900 mb-2">Security Information</h4>
                            <ul class="text-sm text-yellow-800 space-y-1">
                                <li>• Screen locks after <strong>{{ $settings['session_lockout_minutes'] ?? $settings['session_timeout_minutes'] }} minutes</strong> of inactivity</li>
                                <li>• Users are automatically logged out after <strong>{{ $settings['session_timeout_minutes'] }} minutes</strong> of inactivity</li>
                                <li>• Users receive a warning 30 seconds before automatic logout</li>
                                <li>• Activity includes mouse movements, keyboard input, and scrolling</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end pt-6 border-t border-gray-200">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
