<?php

namespace App\Events;


use App\Models\TripHistory;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    private $drivers;

    /**
     * Create a new event instance.
     */
    public function __construct($trip, $drivers)
    {
        $this->trip = $trip;
        $this->drivers = $drivers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        foreach($this->drivers as $drivers){
            $channels = new  PrivateChannel('drivers_'.$drivers['id']);
        }
        return $channels;
    }

    // public function broadcastAs()
    // {
    //     return 'my-event';
    // }
}
