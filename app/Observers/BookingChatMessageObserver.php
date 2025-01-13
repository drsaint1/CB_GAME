<?php

namespace App\Observers;

use App\Events\BookingChatMessageCreated as BookingChatMessageCreatedEvent;
use App\Models\BookingChatMessage;

class BookingChatMessageObserver
{
    /**
     * Handle the BookingChatMessage "created" event.
     */
    public function created(BookingChatMessage $bookingChatMessage): void
    {
        broadcast(new BookingChatMessageCreatedEvent($bookingChatMessage));
    }

    /**
     * Handle the BookingChatMessage "updated" event.
     */
    public function updated(BookingChatMessage $bookingChatMessage): void
    {
        //
    }

    /**
     * Handle the BookingChatMessage "deleted" event.
     */
    public function deleted(BookingChatMessage $bookingChatMessage): void
    {
        //
    }

    /**
     * Handle the BookingChatMessage "restored" event.
     */
    public function restored(BookingChatMessage $bookingChatMessage): void
    {
        //
    }

    /**
     * Handle the BookingChatMessage "force deleted" event.
     */
    public function forceDeleted(BookingChatMessage $bookingChatMessage): void
    {
        //
    }
}
