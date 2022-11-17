<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
class JwtMiddleware
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
        try{
            $user=JWTAuth::parseToken()->authenticate();
        }catch(Exception $e){
            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(["msg"=>"Token Inválido"], 401);
            }
            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(["msg"=>"Token caducado"], 401);
            }
            return response()->json(["msg"=>"Token no encontrado"], 401);
        }
        return $next($request);
    }
}
