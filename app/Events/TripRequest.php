<?php

namespace App\Events;

use App\Models\TripHistory;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    public $drivers;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($trip, $drivers, $user)
    {
        $this->trip = $trip;
        $this->drivers = $drivers;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('general-rides');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $driverIds = [];
        foreach ($this->drivers as $driver) {
            $driverIds[] = $driver->id;
        }

        return [
            'trip' =>$this->trip,
            'driverIds' => $driverIds,
        ];
    }

    public function broadcastAs()
    {
        return 'TripRequest';
    }
}
