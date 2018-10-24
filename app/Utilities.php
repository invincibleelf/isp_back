<?php

namespace App;

use function GuzzleHttp\default_ca_bundle;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class Utilities
{
    public static function getJsonRequestForUpdateStudent($data)
    {

        Log::Info("Convert data to the request format for BUX API");

        $request = [];

        $request['first_name'] = $data->studentDetails->firstname;
        if ($data->studentDetails->middlename !== null) {
            $request['middle_name'] = $data->studentDetails->middlename;
        }

        $request['last_name'] = $data->studentDetails->lastname;

        $request['dob'] = $data->studentDetails->dob;
        $request['national_id_number'] = $data->studentDetails->national_id;
        $request['student_id_number'] = $data->studentDetails->student_id_number;
        if ($data->studentDetails->gender !== null) {
            $request['gender'] = $data->studentDetails->gender;
        }

        $request['email'] = $data->email;
        if ($data->studentDetails->phone !== null) {
            $request['phone'] = $data->phone;
        }

        return $request;
    }

    public static function validatePhoneNumber($code, $phone)
    {
        $regex = "";

        switch ($code) {
            case Config::get('constants.country_codes.AU'):
                $regex = "/^4[0-9]{8}$/";
                break;
            case Config::get('constants.country_codes.CH'):
                $regex = "/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/";
                break;
            default:
                return false;

        }

        if(preg_match($regex,$phone)){
            return true;
        }else{
            return false;
        }
    }
}