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

class DriverArrived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    private $user;

    /**
     * Create a new event instance.
     */
    public function __construct($trip, User $user)
    {
        $this->trip = $trip;
        $this->user = $user;
        // $this->drivers = $drivers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user_' . $this->user->id),
        ];
    }


    public function broadcastAs()
    {
        return 'DriverArrived';
    }

}
