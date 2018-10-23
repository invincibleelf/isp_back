<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next,...$roles)
    {
        if ($request->user() === null) {
            return response([
                'success' => false,
                'message' => "Invalid User Details",
                'status_code' => 401
            ]);
        }

        // TODO Temporary solution need to change in routes and middleware

        if(!$roles){
            $actions = $request->route()->getAction();
            $roles = isset($actions['role']) ? $actions['role'] :null;
        }


        if($request->user()->hasAnyRole($roles) || !$roles) {
            return $next($request);
        }

        return response([
            'success' => false,
            'message' => "Unauthorized Access",
            'status_code' => 403
        ]);
    }
}
