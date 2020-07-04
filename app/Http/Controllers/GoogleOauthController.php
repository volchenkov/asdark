<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Routing\Controller as BaseController;
use Laravel\Socialite\Facades\Socialite;

class GoogleOauthController extends BaseController
{

    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleProviderCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login', ['auth_error' => 1]);
        }

        $existingUser = User::where('email', $user->email)->first();
        if($existingUser) {
            $existingUser->name = $user->name;
            $existingUser->google_id = $user->id;
            $existingUser->save();

            auth()->login($existingUser, true);
        } else {
            return redirect()->route('login', ['unregistered' => 1]);
        }

        return redirect()->to('/');
    }

}
