<?php

namespace App\Services\Events;

use App\Models\Event\EventPassProduct;
use App\Models\Event\Ticket;
use Carbon\Carbon;

class EventStartingPriceService
{
    public function resolve(int $eventId): array
    {
        $prices = Ticket::where('event_id', $eventId)
            ->get()
            ->map(fn (Ticket $ticket) => $this->ticketPrice($ticket))
            ->filter(fn ($price) => $price !== null);

        $passPrices = EventPassProduct::where('event_id', $eventId)
            ->where('active', true)
            ->pluck('price')
            ->map(fn ($price) => ((float) $price) / 100);

        $prices = $prices->concat($passPrices);

        if ($prices->isEmpty()) {
            return ['available' => false, 'free' => false, 'price' => null];
        }

        $minimum = (float) $prices->min();

        return [
            'available' => true,
            'free' => $minimum <= 0,
            'price' => max(0, $minimum),
        ];
    }

    public function ticketPrice(Ticket $ticket): ?float
    {
        if ($ticket->pricing_type === 'free') {
            return 0.0;
        }

        $price = $ticket->pricing_type === 'variation'
            ? $this->variationPrice($ticket)
            : $this->normalPrice($ticket);

        if ($price === null) {
            return null;
        }

        return $this->discountedPrice($ticket, $price);
    }

    private function normalPrice(Ticket $ticket): ?float
    {
        if ((int) $ticket->normal_ticket_slot_enable === 1 && (float) $ticket->slot_seat_min_price > 0) {
            return (float) $ticket->slot_seat_min_price;
        }

        foreach ([$ticket->price, $ticket->f_price] as $price) {
            if ($price !== null && is_numeric($price) && (float) $price >= 0) {
                return (float) $price;
            }
        }

        return null;
    }

    private function variationPrice(Ticket $ticket): ?float
    {
        $variations = json_decode($ticket->variations ?: '[]', true);

        if (!is_array($variations)) {
            return null;
        }

        $prices = [];
        foreach ($variations as $variation) {
            $slotPrice = $variation['slot_seat_min_price'] ?? null;
            $variationPrice = $variation['price'] ?? null;

            if ((int) ($variation['slot_enable'] ?? 0) === 1 && is_numeric($slotPrice) && (float) $slotPrice > 0) {
                $prices[] = (float) $slotPrice;
            } elseif (is_numeric($variationPrice) && (float) $variationPrice >= 0) {
                $prices[] = (float) $variationPrice;
            }
        }

        return $prices === [] ? null : min($prices);
    }

    private function discountedPrice(Ticket $ticket, float $price): float
    {
        if ($ticket->early_bird_discount !== 'enable' || !$ticket->early_bird_discount_date) {
            return $price;
        }

        $expiresAt = Carbon::parse(
            trim($ticket->early_bird_discount_date . ' ' . ($ticket->early_bird_discount_time ?? '23:59:59'))
        );

        if ($expiresAt->isPast()) {
            return $price;
        }

        $discount = (float) $ticket->early_bird_discount_amount;
        if ($ticket->early_bird_discount_type === 'percentage') {
            $discount = $price * $discount / 100;
        }

        return max(0, $price - $discount);
    }
}
