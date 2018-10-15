<?php

namespace App;

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
}