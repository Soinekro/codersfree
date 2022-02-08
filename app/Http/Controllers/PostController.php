<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function store(Request $request){

        if (auth()->user()->accessToken->expires_at <= now()) {
            $this->resolveAuthorization();
        }
        /*--------------------------------------------------------*/
        $response = Http::withHeaders([
            'Accept'=>'application/json',
            'Authorization'=> 'Bearer '.auth()->user()->accessToken->access_token,
        ])->post('http://api.codersfree.test/v1/post/posts',[
            'name'=>'nombre Prueba',
            'slug'=>'nombre-Prueba',
            'extract'=>'nnasdgnjsdfgba',
            'body'=>'ajkskjfdsf',
            'category_id'=>1,
        ]);

        return $response->json();
    }
}
