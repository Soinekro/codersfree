<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use Illuminate\Support\Facades\Http;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $response = Http::withHeaders([
            'Accept'=>'application/json',
        ])->post('http://api.codersfree.test/v1/user/register',$request->all());

        if ($response->status() == 422) {
            return back()->withErrors($response->json()['errors']);
        }

        $service = $response->json();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        //requiriendo token a api

        $response = Http::withHeaders([
            'Accept'=>'aplication/json',
        ])->post('http://api.codersfree.test/oauth/token',[
            'grant_type'=>'password',
            'client_id' => '9585439f-2b66-4daf-80d2-de0febd081cf',
            'client_secret' => 'e6d2Wl3sahNsGxCbVVeZoI1OBiSi9ilBYbBKtrX4',
            'username' =>  $request->email,
            'password'  => $request->password,
        ]);

        $access_token = $response->json();

        //return $service;
        $user->accessToken()->create([
            'service_id' =>$service['data']['id'],
            'access_token'  => $access_token['access_token'],
            'refresh_token'=>$access_token['refresh_token'],
            'expires_at'=>now()->addSecond($access_token['expires_in']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
