<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        $userDetails = $this->getUserDetails();


        return [
            "status_code" => 200,
            "success" => true,
            "token" => auth()->issue(),
            "role" => $this->role->name,
            $this->role->name => $userDetails

        ];
    }

    protected function getUserDetails()
    {
        $details = [];
        $details["id"] = $this->id;
        $details['email'] = $this->email;
        $details['phone'] = $this->phone;

        switch ($this->role->name) {
            case "student":

                $details['firstName'] = $this->studentDetails->firstname;
                $details['middleName'] = $this->studentDetails->middlename;
                $details['lastName'] = $this->studentDetails->lastname;
                $details['chineseFirstName'] = $this->studentDetails->chinese_firstname;
                $details['chineseLastName'] = $this->studentDetails->chinese_lastname;
                $details['dob'] = $this->studentDetails->dob;
                $details['gender'] = $this->studentDetails->gender;
                $details['nationalId'] = $this->studentDetails->national_id;
                $details['studentIdNumber'] = $this->studentDetails->student_id_number;
                if ($this->studentDetails->councilor !== null) {
                    $details['councilor'] = $this->getCouncilorDetails($this->studentDetails->councilor);
                    $details['agent'] = $this->getAgentDetails($this->studentDetails->councilor->agent);
                }

                break;
            case "councilor":
                $details['firstName'] = $this->councilorDetails->firstname;
                $details['middleName'] = $this->councilorDetails->middlename;
                $details['lastName'] = $this->councilorDetails->lastname;
                $details['nationalId'] = $this->councilorDetails->national_id;
                if ($this->councilorDetails->agent !== null) {
                    $details['agent'] = $this->getAgentDetails($this->councilorDetails->agent);
                }
                break;
            case "agent":
                $details["agentName"] = $this->agentDetails->name;
                $details["location"] = $this->agentDetails->location;
                $details["nationalId"] = $this->agentDetails->national_id;
                $details["legalRegistrationNumber"] = $this->agentDetails->legal_registration_number;
                $details["validBankOpening"] = $this->agentDetails->valid_bank_opening;
                $details["bankAccountNumber"] = $this->agentDetails->bank_account_number;
                $details["bankAccountName"] = $this->agentDetails->bank_account_name;
                break;

            case "payer":
                $details['firstName'] = $this->payerDetails->firstname;
                $details['middleName'] = $this->payerDetails->middlename;
                $details['lastName'] = $this->payerDetails->lastname;
                $details['chineseFirstName'] = $this->payerDetails->chinese_firstname;
                $details['chineseLastName'] = $this->payerDetails->chinese_lastname;
                $details['dob'] = $this->payerDetails->dob;
                $details['gender'] = $this->payerDetails->gender;
                $details['nationalId'] = $this->payerDetails->national_id;
                $details["bankAccountNumber"] = $this->payerDetails->bank_account_number;
                break;


        }

        return $details;
    }

    protected function getCouncilorDetails($councilor)
    {
        $c = [];
        $c['id'] = $councilor->id;
        $c["firstName"] = $councilor->firstname;
        $c["middleName"] = $councilor->middlename;
        $c["lastName"] = $councilor->lastname;
        $c["nationalId"] = $councilor->national_id;
        return $c;

    }

    protected function getAgentDetails($a)
    {
        $agent = [];
        $agent["id"] = $a->id;
        $agent["agentName"] = $a->name;
        $agent["location"] = $a->location;
        $agent["nationalId"] = $a->national_id;
        $agent["legalRegistrationNumber"] = $a->legal_registration_number;
        $agent["validBankOpening"] = $a->valid_bank_open;
        $agent["bankAccountNumber"] = $a->bank_account_number;
        $agent["bankAccountName"] = $a->bank_account_name;
        return $agent;
    }


}
