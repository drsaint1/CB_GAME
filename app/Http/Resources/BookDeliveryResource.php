<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'availability' => $this->availability,
            'avatar' => Storage::disk('rider_passport')->url($this->avatar) ?? null,
            'vehicle' => $this->logistic_vehicles
        ];
    }
}
