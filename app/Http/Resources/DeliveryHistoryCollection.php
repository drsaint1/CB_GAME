<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryHistoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        return [
            'id' => $this->id,
            'riders_id' => $this->riders_id,
            'origins_address' => $this->from_address,
            'destination_address' => $this->to_address,
            'origins_lat' => $this->from_lat,
            'origins_long' => $this->from_long,
            'destination_lat' => $this->to_lat,
            'destination_long' => $this->to_long,
            't_fare' => $this->t_fare,
            'payment_type' => $this->payment_type,
            'payment_status' => $this->payment_status,
            'delivery_status' => $this->delivery_status,
            'created_at' => $this->created_at,
            // 'logistic_vehicles' => $this->logistic_vehicles,
            // 'user' => $this->users
        ];
    }
}
