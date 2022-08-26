<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
class JwtStaticMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // try{
        //     $user=JWTAuth::parseToken()->authenticate();
        // }catch(Exception $e){
        //     if($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
        //         return response()->json(["msg"=>"Token Invalido"]);
        //     }
        //     if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
        //         return response()->json(["msg"=>"El Token está caducado"]);
        //     }
        //     return response()->json(["msg"=>"Token no encontrado"]);
        // }
        // dd($request->all());
        $headers = getallheaders();
        // $headers->Authorization;
        if ($headers['Authorization'] == 'Bearer '.config('jwt.token_dpa')) {
            return $next($request);
        } else {
            return response()->json(["msg"=>"Token Invalido"]);
            // return response()->json(["msg"=>config('jwt.token_dpa')]);
        }
    }
}
