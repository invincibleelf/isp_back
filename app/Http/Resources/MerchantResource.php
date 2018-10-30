<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $merchant['id'] = $this->id;
        $merchant['alias'] = $this->alias;
        $merchant['name'] = $this->full_name;
        $merchant['description'] = $this->description;

        $merchant['services'] = $this->getServices();

        return $merchant;
    }

    protected function  getServices(){
        $services = [];
        foreach ($this->services as $s) {

            $service['id'] = $s->id;
            $service['name'] = $s->name;
            $service['description'] = $s->description;

            array_push($services,$service);
        }
        return $services;
    }
}
