<?php
/**
 * Created by PhpStorm.
 * User: invincibleelf
 * Date: 22/10/18
 * Time: 11:40 AM
 */

namespace App\Services;

use App\AgentDetail;
use App\CouncilorDetail;
use App\PayerDetail;
use App\Role;

use App\StudentDetail;
use App\Utilities;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class UserServiceImpl implements UserService
{


    public function createPayer($payer, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $payer->phone = $credentials['countryCode'] . $credentials['phone'];
        }
        $payer->email = $credentials['email'];
        $payer->password = bcrypt($credentials['password']);
        $payer->verified = true;
        $payer->status = Config::get('enums.status.ACTIVE');
        $payer->role()->associate((Role::where('name', 'payer')->first()));

        Log::info("Save Payer");
        $payer->save();

        $payerDetail = new PayerDetail();
        $payerDetail->firstname = $credentials['firstName'];
        $payerDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $payerDetail->lastname = $credentials['lastName'];
        $payerDetail->chinese_firstname = array_key_exists('chineseFirstName', $credentials) ? $credentials['chineseFirstName'] : null;
        $payerDetail->chinese_lastname = array_key_exists('chineseLastName', $credentials) ? $credentials['chineseLastName'] : null;
        $payerDetail->dob = $credentials['dob'];
        $payerDetail->gender = array_key_exists("gender", $credentials) ? $credentials['gender'] : null;
        $payerDetail->national_id = $credentials['nationalId'];

        $payerDetail->student()->associate(Auth::user()->studentDetails);


        Log::info("Save Payer details ");
        $payer->payerDetails()->save($payerDetail);


        return $payer;
    }


    public function updatePayer($payer, $credentials)
    {

        if (array_key_exists('countryCode', $credentials)) {
            $payer->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $payer->payerDetails->firstname = $credentials['firstName'];
        $payer->payerDetails->lastname = $credentials['lastName'];
        $payer->payerDetails->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $payer->payerDetails->chinese_firstname = array_key_exists('chineseFirstName', $credentials) ? $credentials['chineseFirstName'] : null;
        $payer->payerDetails->chinese_lastname = array_key_exists('chineseLastName', $credentials) ? $credentials['chineseFirstName'] : null;
        $payer->payerDetails->dob = $credentials['dob'];
        $payer->payerDetails->gender = array_key_exists('gender', $credentials) ? $credentials['gender'] : null;
        $payer->payerDetails->national_id = $credentials['nationalId'];

        $payer->payerDetails->save();

        $payer->save();

        return $payer;
    }

    public function createStudent($student, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $student->phone = $credentials['countryCode'] . $credentials['phone'];
        }
        $student->email = $credentials['email'];
        $student->password = bcrypt($credentials['password']);
        $student->verified = true;
        $student->status = Config::get('enums.status.ACTIVE');
        $student->role()->associate((Role::where('name', 'student')->first()));

        Log::info("Save Student ");
        $student->save();

        $studentDetail = new StudentDetail();
        $studentDetail->firstname = $credentials['firstName'];
        $studentDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $studentDetail->lastname = $credentials['lastName'];
        $studentDetail->chinese_firstname = array_key_exists('chineseFirstName', $credentials) ? $credentials['chineseLastName'] : null;
        $studentDetail->chinese_lastname = array_key_exists('chineseFirstName', $credentials) ? $credentials['chineseLastName'] : null;
        $studentDetail->dob = $credentials['dob'];
        $studentDetail->gender = array_key_exists("gender", $credentials) ? $credentials['gender'] : null;
        $studentDetail->national_id = $credentials['nationalId'];
        $studentDetail->student_id_number = $credentials['studentIdNumber'];

        Log::info("Save Student details ");
        $student->studentDetails()->save($studentDetail);

        return $student;
    }


    public function createStudentAtBux($student)
    {
        Log::info("Create Student " . $student->email . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->post(Config::get('constants.bux_base_url') . Config::get('constants.bux_student'), ['json' => Utilities::getJsonRequestForUpdateStudent($student)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;
    }


    public function updateStudent($student, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $student->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $student->studentDetails->firstname = $credentials['firstName'];
        $student->studentDetails->lastname = $credentials['lastName'];
        $student->studentDetails->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $student->studentDetails->chinese_firstname = array_key_exists('chineseFirstName', $credentials) ? $credentials['chineseFirstName'] : null;
        $student->studentDetails->chinese_lastname = array_key_exists('chineseLastName', $credentials) ? $credentials['chineseLastName'] : null;
        $student->studentDetails->dob = $credentials['dob'];
        $student->studentDetails->gender = array_key_exists('gender', $credentials) ? $credentials['gender'] : null;
        $student->studentDetails->national_id = $credentials['nationalId'];
        $student->studentDetails->student_id_number = $credentials['studentIdNumber'];
        Log::info("Update Student with id " . $student->id);
        $student->save();
        $student->studentDetails->save();

        return $student;
    }

    public function updateStudentAtBux($student)
    {
        Log::info("Update Student id " . $student->id . " with bux_id " . $student->studentDetails->bux_id . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->put(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . $student->studentDetails->bux_id, ['json' => Utilities::getJsonRequestForUpdateStudent($student)]);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;
    }

    public function deleteStudentAtBux($student)
    {
        Log::info("Delete Student id " . $student->id . " with bux_id " . $student->studentDetails->bux_id . "in BUX API " . Config::get('constants.bux_base_url'));

        $buxAPI = new Client([
            'base_uri' => Config::get('constants.bux_base_url'),
            'timeout' => 2.0
        ]);

        Log::info("Request to Bux API");
        $buxResponse = $buxAPI->delete(Config::get('constants.bux_base_url') . Config::get('constants.bux_student') . $student->studentDetails->bux_id);
        //Get body of the response in JSON (Must use decode because of the bug )
        $contents = json_decode($buxResponse->getBody());

        return $contents;
    }

    public function createCouncilor($councilor, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $councilor->phone = $credentials['countryCode'] . $credentials['phone'];
        }
        $councilor->email = $credentials['email'];
        $councilor->password = bcrypt($credentials['password']);
        $councilor->verified = true;
        $councilor->role()->associate((Role::where('name', 'councilor')->first()));
        $councilor->status = Config::get('enums.status.ACTIVE');
        Log::info("Save Councilor ");
        $councilor->save();


        $councilorDetail = new CouncilorDetail();
        $councilorDetail->firstname = $credentials['firstName'];
        $councilorDetail->middlename = array_key_exists('middleName', $credentials) ? $credentials['middleName'] : null;
        $councilorDetail->lastname = $credentials['lastName'];
        $councilorDetail->national_id = $credentials['nationalId'];

        $councilorDetail->agent()->associate(Auth::user()->agentDetails);


        Log::info("Save Councilor Details ");
        $councilor->councilorDetails()->save($councilorDetail);

        return $councilor;
    }

    public function updateCouncilor($councilor, $credentials)
    {
        $councilor->councilorDetails->firstname = $credentials['firstName'];
        $councilor->councilorDetails->lastname = $credentials['lastName'];
        $councilor->councilorDetails->middlename = in_array('middleName', $credentials) ? $credentials['middleName'] : null;
        $councilor->councilorDetails->national_id = $credentials['nationalId'];

        if (array_key_exists('countryCode', $credentials)) {
            $councilor->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $councilor->save();
        $councilor->councilorDetails->save();

        return $councilor;
    }

    public function createAgent($agent, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $agent->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $agent->email = $credentials['email'];
        $agent->password = bcrypt($credentials['password']);
        $agent->verified = false;
        $agent->status = Config::get('enums.status.ACTIVE');
        $agent->role()->associate((Role::where('name', $credentials['role'])->first()));

        Log::info("Save Agent ");
        $agent->save();

        $agentDetails = new AgentDetail();
        $agentDetails->name = $credentials['agentName'];
        $agentDetails->location = $credentials['location'];
        $agentDetails->national_id = $credentials['nationalId'];
        $agentDetails->legal_registration_number = array_key_exists('legalRegistrationNumber', $credentials) ? $credentials['legalRegistrationNumber'] : null;
        $agentDetails->valid_bank_opening = $credentials['validBankOpening'];
        $agentDetails->bank_account_number = $credentials['bankAccountNumber'];
        $agentDetails->bank_account_name = $credentials['bankAccountName'];

        Log::info("Save Agent Details for agent " . $agent->email);
        $agent->agentDetails()->save($agentDetails);

        return $agent;
    }


    public function updateAgent($agent, $credentials)
    {
        if (array_key_exists('countryCode', $credentials)) {
            $agent->phone = $credentials['countryCode'] . $credentials['phone'];
        }

        $agent->agentDetails->name = $credentials['agentName'];
        $agent->agentDetails->national_id = $credentials['nationalId'];
        $agent->agentDetails->location = $credentials['location'];
        $agent->agentDetails->legal_registration_number = array_key_exists('legalRegistrationNumber', $credentials) ? $credentials['legalRegistrationNumber'] : null;
        $agent->agentDetails->bank_account_number = $credentials['bankAccountNumber'];
        $agent->agentDetails->bank_account_name = $credentials['bankAccountName'];
        $agent->agentDetails->valid_bank_opening = $credentials['validBankOpening'];

        $agent->save();
        $agent->agentDetails->save();

        return $agent;
    }
}