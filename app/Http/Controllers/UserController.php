<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\User;
use http\Env\Response;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function showProfile($id) {

        $user = User::find($id);

        return response(["u"=>$user]);

        return new UserResource($user);
    }
}
