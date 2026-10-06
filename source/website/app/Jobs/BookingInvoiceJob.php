<?php

namespace App\Jobs;

use App\Services\Tickets\TicketDeliveryService;
use App\Models\Event\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; 
    public $tries = 3;   

    public $booking_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($booking_id)
    {
        $this->booking_id = $booking_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TicketDeliveryService $delivery)
    {
        $bookingInfo = Booking::findOrFail($this->booking_id);

        // One canonical delivery path: issue secure per-attendee tickets, render every
        // ticket/QR into the PDF, persist the invoice and send the customer email.
        // This replaces the legacy invoice path that could render only one ticket
        // while the customer dashboard correctly showed all issued credentials.
        if (!$delivery->deliver($bookingInfo)) {
            throw new \RuntimeException('Ticket delivery could not be completed for booking ' . $bookingInfo->id);
        }
    }
}
