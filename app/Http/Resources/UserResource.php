<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $details = [];

        switch($this->role->name) {
            case "student":
                $details = $this->studentDetails;
                $details->load("councilor");
                break;
            case "councilor":
                $details = $this->councilorDetails;
                $details->load("agent");
                break;
            case "agent":
                $details = $this->agentDetails;
                $councilors  = $this->councilolrs;


        }
        return [
            "userDetails"=>$details
        ];


        //TODO Manage Return parameter according to the need from cient
    }
}
