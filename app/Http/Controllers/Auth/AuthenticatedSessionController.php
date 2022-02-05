<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Facades\Http;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        /* $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME); */

        $request->validate([
            'email'=>'required|string|email',
            'password' => 'required|string'
        ]);

        $response = Http::withHeaders([
            'Accept'=>'aplication/json',
        ])->post('http://api.codersfree.test/v1/login',[
            'email' =>  $request->email,
            'password'  => $request->password,
        ]);

        if($response->status() == 404){
            return back()->withErrors('Las credenciales no coinciden');
        }

        $service = $response->json();

        $user = User::updateOrcreate([
            'email'=>$request->email,
        ],$service['data']);

        if (!$user->accessToken) {

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
            dd($access_token);
        }

        Auth::login($user,$request->remember);

        return redirect()->intended(RouteServiceProvider::HOME);

        //return dd($response->json());
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
