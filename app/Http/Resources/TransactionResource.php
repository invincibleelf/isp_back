<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        $details = [];
        $details['id'] = $this->id;
        $details['transactionSN'] = $this->transaction_sn;
        $details['amount'] = $this->amount;
        $details['merchant'] = $this->merchant;
        $details['services'] = json_decode($this->service_ids);
        $details['status'] = $this->status;
        $details['student'] = $this->getStudentInfo();

        if ($this->payer !== null) {
            $details['payer'] = $this->getPayerInfo();
        }

        if ($this->payment_info !== null) {
            $details['paymentMethod'] = $this->getPaymentMethodInfo();
        }

        if ($this->pay_time !== null) {

            $details['paymentTime'] = $this->pay_time;
        }

        return $details;
    }

    protected function getStudentInfo()
    {

        //@id should always be the id of the User model
        $student["id"] = $this->student->user->id;
        $student['email'] = $this->student->user->email;
        $student['phone'] = $this->student->user->phone;
        $student['firstName'] = $this->student->firstname;
        $student['middleName'] = $this->student->middlename;
        $student['lastName'] = $this->student->lastname;
        $student['dob'] = $this->student->dob;
        $student['gender'] = $this->student->gender;
        $student['nationalId'] = $this->student->national_id;
        $student['studentIdNumber'] = $this->student->student_id_number;

        return $student;
    }

    protected function getPayerInfo()
    {

        //@id should always be the id of the User model
        $payer["id"] = $this->payer->user->id;
        $payer['email'] = $this->payer->user->email;
        $payer['phone'] = $this->payer->user->phone;
        $payer['firstName'] = $this->payer->firstname;
        $payer['middleName'] = $this->payer->middlename;
        $payer['lastName'] = $this->payer->lastname;
        $payer['dob'] = $this->payer->dob;
        $payer['gender'] = $this->payer->gender;
        $payer['nationalId'] = $this->payer->national_id;

        return $payer;
    }

    protected function getPaymentMethodInfo()
    {
        $paymentMethod['id'] = $this->paymentMethod->id;
        $paymentMethod['name'] = $this->paymentMethod->name;
        return $paymentMethod;
    }
}
