<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'deviceId' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'g_recaptcha_response' => ['required', new \App\Rules\RecaptchaV2()],
        ]);

        $deviceId = (string) $request->input('deviceId');
        $deviceData = $this->firebaseService->getDeviceRegistrationData($deviceId);

        if (is_null($deviceData)) {
            throw ValidationException::withMessages([
                'deviceId' => 'Invalid Device ID',
            ]);
        }

        $owner = is_array($deviceData) ? ($deviceData['owner'] ?? null) : null;
        if (!is_null($owner) && (is_string($owner) ? trim($owner) !== '' : true)) {
            throw ValidationException::withMessages([
                'deviceId' => 'Device already linked',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
