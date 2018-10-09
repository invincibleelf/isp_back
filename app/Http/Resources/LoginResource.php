<?php

namespace App\Http\Resources;
use App\Http\Resources\UserResource;

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
        return [
            "email" => $this->email,
            "token" =>auth()->issue(),
            "role"=>$this->role->name,
            "user"=>new UserResource($this)
        ];
    }
}
