<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function showProfile()
    {

        $user = Auth::user();

        return new UserResource($user);
    }



}
