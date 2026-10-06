<?php

namespace Tests\Unit;

use App\Models\Event\Ticket;
use App\Services\Events\EventStartingPriceService;
use Tests\TestCase;

class EventStartingPriceServiceTest extends TestCase
{
    private EventStartingPriceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EventStartingPriceService();
    }

    public function test_normal_ticket_uses_configured_price(): void
    {
        $ticket = new Ticket(['pricing_type' => 'normal', 'price' => 499]);

        $this->assertSame(499.0, $this->service->ticketPrice($ticket));
    }

    public function test_variation_ticket_uses_lowest_variation_instead_of_zero_base_price(): void
    {
        $ticket = new Ticket([
            'pricing_type' => 'variation',
            'price' => 0,
            'variations' => json_encode([
                ['price' => 799, 'slot_enable' => 0],
                ['price' => 499, 'slot_enable' => 0],
            ]),
        ]);

        $this->assertSame(499.0, $this->service->ticketPrice($ticket));
    }

    public function test_slot_enabled_ticket_uses_slot_minimum(): void
    {
        $ticket = new Ticket([
            'pricing_type' => 'normal',
            'price' => 999,
            'normal_ticket_slot_enable' => 1,
            'slot_seat_min_price' => 349,
        ]);

        $this->assertSame(349.0, $this->service->ticketPrice($ticket));
    }

    public function test_free_ticket_resolves_to_zero(): void
    {
        $ticket = new Ticket(['pricing_type' => 'free']);

        $this->assertSame(0.0, $this->service->ticketPrice($ticket));
    }
}
