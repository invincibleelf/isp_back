<?php
namespace App\Utility;

use App\AgentDetail;
use App\CouncilorDetail;
use App\PayerDetail;
use App\Role;
use App\StudentDetail;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class Utility
{


    public static function getEntityByCurrentUser()
    {
        $user = Auth::user();
        $entity = "";

        switch ($user->role_id) {
            case "1":
                $entity = StudentDetail::where('user_id', $user->id)->first();
               
                break;
            case "2":
                $entity = AgentDetail::where('user_id', $user->id)->first();
                break;
            case "3":
                $entity = CouncilorDetail::where('user_id', $user->id)->first();
                break;
            case "4":
                $entity = PayerDetail::where('user_id', $user->id)->first();
                break;
            default:
                # code...
                break;
        }

        return $entity;
    }


    
}