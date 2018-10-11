<?php

namespace App\Http\Resources;

use http\Env\Response;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        $userInfo = $this->getUserInfo();

        return [
            "status_code" => 200,
            "success" => true,
            $this->role->name => $userInfo

        ];

    }

    protected function getUserInfo()
    {
        $details =[];
        $details["id"] = $this->id;
        $details['email'] = $this->email;
        $details['phone'] = $this->phone;
        switch ($this->role->name) {
            case "student":

                $details['firstName'] = $this->studentDetails->firstname;
                $details['middleName'] = $this->studentDetails->middlename;
                $details['lastName'] = $this->studentDetails->lastname;
                $details['dob'] = $this->studentDetails->dob;
                $details['gender'] = $this->studentDetails->gender;
                $details['nationalId'] = $this->studentDetails->national_id;

                break;
            case "councilor":
                $details['firstName'] = $this->councilorDetails->firstname;
                $details['middleName'] = $this->councilorDetails->middlename;
                $details['lastName'] = $this->councilorDetails->lastname;
                $details['nationalId'] = $this->councilorDetails->national_id;
                break;
            case "agent":
                $details["agentName"] = $this->agentDetails->name;
                $details["location"] = $this->agentDetails->location;
                $details["nationalId"] = $this->agentDetails->national_id;
                $details["legalRegistrationNumber"] = $this->agentDetails->legal_registration_number;
                $details["validBankOpening"] = $this->agentDetails->valid_bank_open;
                $details["bankAccountNumber"] = $this->agentDetails->bank_account_number;
                $details["bankAccountName"] = $this->agentDetails->bank_account_name;
        }

        return $details;
    }
}
