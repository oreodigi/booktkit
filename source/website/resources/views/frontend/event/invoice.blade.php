<!DOCTYPE html>
<html lang="{{ $language->code }}" dir="{{ $language->direction == 1 ? 'rtl' : 'ltr' }}">
@php
    App::setLocale($language->code);

    $websiteInfo = $websiteInfo ?? \App\Models\BasicSettings\Basic::first();
    $primaryColor = '#' . ($event->ticket_background_color ?? $websiteInfo->primary_color ?? '2ccfd4');
    $currency = $bookingInfo->currencyText ?? ($websiteInfo->base_currency_text ?? '');
    $position = $bookingInfo->currencyTextPosition ?? 'right';

    $formatMoney = function ($amount) use ($currency, $position) {
        $value = number_format((float) $amount, 2, '.', '');
        if (empty($currency)) {
            return $value;
        }

        return $position === 'left' ? $currency . ' ' . $value : $value . ' ' . $currency;
    };

    $filePath = function ($relativePath) {
        if (empty($relativePath)) {
            return null;
        }

        $absolutePath = public_path($relativePath);

        if (!file_exists($absolutePath)) {
            return null;
        }

        return str_replace('\\', '/', $absolutePath);
    };

    $eventImage = $filePath('assets/admin/img/event_ticket/' . ($event->ticket_image ?? ''));
    $eventThumbnail = $filePath('assets/admin/img/event/thumbnail/' . ($event->thumbnail ?? ''));
    $fallbackImage = $filePath('assets/admin/img/noimage.jpg');
    $displayImage = $eventImage ?: ($eventThumbnail ?: $fallbackImage);
    $imageSize = !empty($displayImage) && file_exists($displayImage) ? @getimagesize($displayImage) : null;
    $isLowResImage = !empty($imageSize) && (($imageSize[0] ?? 0) < 1200 || ($imageSize[1] ?? 0) < 1200);

    $ticketLogo = $filePath('assets/admin/img/event_ticket_logo/' . ($event->ticket_logo ?? ''));
    $siteLogo = $filePath('assets/admin/img/' . ($websiteInfo->logo ?? ''));
    $displayLogo = $ticketLogo ?: $siteLogo;

    $gatewayType = strtolower((string) $bookingInfo->gatewayType);
    $paymentMethod = trim((string) $bookingInfo->paymentMethod);

    if ($bookingInfo->paymentStatus === 'free') {
        $paymentMethodLabel = __('Free');
    } elseif ($gatewayType === 'offline') {
        $paymentMethodLabel = $paymentMethod !== '' ? __('Offline') . ' - ' . $paymentMethod : __('Offline');
    } elseif ($paymentMethod !== '') {
        $paymentMethodLabel = __('Online') . ' - ' . $paymentMethod;
    } else {
        $paymentMethodLabel = '-';
    }

    if ($bookingInfo->paymentStatus === 'completed') {
        $paymentStatusLabel = __('Completed');
    } elseif ($bookingInfo->paymentStatus === 'pending') {
        $paymentStatusLabel = __('Pending');
    } elseif ($bookingInfo->paymentStatus === 'rejected') {
        $paymentStatusLabel = __('Rejected');
    } elseif ($bookingInfo->paymentStatus === 'free') {
        $paymentStatusLabel = __('Free');
    } else {
        $paymentStatusLabel = '-';
    }

    $billingAddressParts = [
        $bookingInfo->address,
        $bookingInfo->city,
        $bookingInfo->state,
        $bookingInfo->zip_code,
        $bookingInfo->country,
    ];

    $billingAddress = collect($billingAddressParts)
        ->filter(function ($value) {
            return !is_null($value) && trim((string) $value) !== '';
        })
        ->implode(', ');

    $duration = $event->duration ?? optional($bookingInfo->evnt)->duration;
    $isVariationBooking = !empty($bookingInfo->variation);

    $ticketTitles = [];
    $ticketTypes = [];

    if ($isVariationBooking) {
        $variationRows = json_decode($bookingInfo->variation, true) ?: [];
        $variationTicketIds = collect($variationRows)
            ->pluck('ticket_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($variationTicketIds)) {
            $ticketTypes = \App\Models\Event\Ticket::whereIn('id', $variationTicketIds)
                ->select('id', 'pricing_type')
                ->get()
                ->keyBy('id')
                ->toArray();

            $ticketContents = \App\Models\Event\TicketContent::whereIn('ticket_id', $variationTicketIds)
                ->where('language_id', $language->id)
                ->get()
                ->keyBy('ticket_id');

            if ($ticketContents->count() !== count($variationTicketIds)) {
                $fallbackContents = \App\Models\Event\TicketContent::whereIn('ticket_id', $variationTicketIds)
                    ->get()
                    ->groupBy('ticket_id')
                    ->map(function ($items) {
                        return $items->first();
                    });
                $ticketContents = $fallbackContents->merge($ticketContents);
            }

            foreach ($ticketContents as $ticketId => $ticketContent) {
                $ticketTitles[$ticketId] = $ticketContent->title ?? '';
            }
        }
    }

    $ticketItems = [];

    if ($isVariationBooking) {
        foreach ($variationRows as $variation) {
            $qrPath = $filePath('assets/admin/qrcodes/' . $bookingInfo->booking_id . '__' . ($variation['unique_id'] ?? '') . '.svg');

            $ticketId = $variation['ticket_id'] ?? null;
            $baseTitle = $ticketId && array_key_exists($ticketId, $ticketTitles) ? $ticketTitles[$ticketId] : '';
            $variationName = $variation['name'] ?? '';
            $pricingType = $ticketId && array_key_exists($ticketId, $ticketTypes) ? ($ticketTypes[$ticketId]['pricing_type'] ?? null) : null;

            if ($pricingType === 'variation' && $baseTitle !== '' && $variationName !== '') {
                $ticketLabel = $baseTitle . ' - ' . $variationName;
            } elseif ($variationName !== '') {
                $ticketLabel = $variationName;
            } elseif ($baseTitle !== '') {
                $ticketLabel = $baseTitle;
            } else {
                $ticketLabel = __('General Ticket');
            }

            $ticketItems[] = [
                'qr' => $qrPath,
                'label' => $ticketLabel,
                'slot_name' => $variation['slot_name'] ?? null,
                'seat_name' => $variation['seat_name'] ?? null,
            ];
        }
    } else {
        for ($i = 1; $i <= (int) $bookingInfo->quantity; $i++) {
            $ticketItems[] = [
                'qr' => $filePath('assets/admin/qrcodes/' . $bookingInfo->booking_id . '__' . $i . '.svg'),
                'label' => __('General Ticket'),
                'slot_name' => null,
                'seat_name' => null,
            ];
        }
    }

    if (isset($issuedTickets) && !empty($issuedTickets)) {
        $ticketItems = [];
        foreach ($issuedTickets as $issuedTicket) {
            $ticketItems[] = [
                'qr' => $filePath('assets/admin/qrcodes/secure_' . $issuedTicket['uuid'] . '.svg'),
                'label' => $issuedTicket['ticket_name'] ?? __('Ticket'),
                'slot_name' => null,
                'seat_name' => null,
            ];
        }
    }
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ 'Invoice | ' . config('app.name') }}</title>
    <style>
        @page {
            margin: 10px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            background: #ececec;
            color: #161616;
        }

        .page-wrap {
            width: 760px;
            margin: 6px auto;
            background: #ececec;
            padding: 10px 8px 8px;
            box-sizing: border-box;
        }

        .ticket-card {
            border: 2px solid {{ $primaryColor }};
            background: #f7f7f7;
            padding: 0;
            box-sizing: border-box;
            min-height: 390px;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 6px;
            padding-top: 4px;
            line-height: 1;
        }

        .logo-wrap img {
            max-height: 36px;
            max-width: 150px;
            display: block;
            margin: 0 auto;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .layout-table td {
            vertical-align: top;
        }

        .left-col {
            width: 265px;
            padding: 0;
            min-height: 390px;
            position: relative;
        }

        .left-col.low-res-cell {
            min-height: 0;
        }

        .mid-col {
            width: auto;
            padding: 12px 2px 10px 14px;
            box-sizing: border-box;
        }

        .right-col {
            width: 104px;
            text-align: right;
            padding-top: 12px;
            padding-right: 10px;
            padding-left: 0;
        }

        .event-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
            border: 0;
            object-fit: cover;
        }

        .event-image.low-res {
            position: static;
            width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            background: transparent;
        }

        .qr-image {
            width: 92px;
            height: 92px;
            display: block;
            margin-left: auto;
            margin-right: 2px;
            margin-top: 0;
            border: 0;
        }

        .event-title {
            margin: 0 0 4px;
            font-size: 15px;
            line-height: 1.2;
            font-weight: 700;
            color: #0e0e0e;
        }

        .sub-title {
            margin: 0 0 5px;
            font-size: 12px;
            line-height: 1.3;
            font-weight: 700;
            color: #111;
        }

        .text-line {
            margin: 0 0 5px;
            font-size: 11px;
            line-height: 1.38;
            color: #222;
            font-weight: 500;
        }

        .ticket-name {
            margin: 0 0 5px;
            font-size: 13px;
            line-height: 1.32;
            font-weight: 600;
            color: #1a1a1a;
        }

        .divider {
            border-top: 1px solid #d0d0d0;
            margin: 6px 0;
        }

        .label {
            margin: 0 0 2px;
            font-size: 9px;
            line-height: 1.24;
            color: #9a9a9a;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.18px;
        }

        .value {
            margin: 0;
            font-size: 12px;
            line-height: 1.35;
            color: #1f1f1f;
            font-weight: 600;
        }

        .value-regular {
            margin: 0;
            font-size: 11px;
            line-height: 1.35;
            color: #1f1f1f;
            font-weight: 500;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .grid td {
            width: 33.333333%;
            vertical-align: top;
            padding: 0 10px 0 0;
            box-sizing: border-box;
        }

        .grid td:last-child {
            padding-right: 0;
        }

        .grid .value {
            font-size: 12px;
            line-height: 1.35;
        }

        .info-box {
            border: 2px solid {{ $primaryColor }};
            background: #f7f7f7;
            border-radius: 2px;
            padding: 10px 10px 9px;
            margin-top: 12px;
            page-break-inside: auto;
        }

        .info-box,
        .info-box p,
        .info-box li,
        .info-box span,
        .info-box div {
            font-size: 11px !important;
            line-height: 1.52 !important;
            color: #202020 !important;
            font-family: DejaVu Sans, sans-serif !important;
        }

        .info-box strong,
        .info-box b {
            font-size: 13px !important;
            line-height: 1.35 !important;
            font-weight: 700 !important;
            color: #111 !important;
        }

        .info-box p,
        .info-box li,
        .info-box div {
            margin-top: 0 !important;
            margin-bottom: 5px !important;
        }

        .info-box ul,
        .info-box ol {
            margin: 4px 0 4px 14px;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        .rtl {
            text-align: right;
        }
    </style>
</head>

<body>
    @forelse ($ticketItems as $ticketItem)
        <div class="page-wrap">
            @if (!empty($displayLogo))
                <div class="logo-wrap">
                    <img src="{{ $displayLogo }}" alt="logo">
                </div>
            @endif

            <div class="ticket-card">

                <table class="layout-table">
                    <tr>
                        <td class="left-col{{ $isLowResImage ? ' low-res-cell' : '' }}">
                            @if (!empty($displayImage))
                                <img src="{{ $displayImage }}" alt="event-image" class="event-image{{ $isLowResImage ? ' low-res' : '' }}">
                            @endif
                        </td>

                        <td class="mid-col">
                            <p class="event-title {{ $language->direction == 1 ? 'rtl' : '' }}">{{ $eventInfo->title ?? __('Event Ticket') }}</p>
                            <div class="divider"></div>

                            <p class="sub-title {{ $language->direction == 1 ? 'rtl' : '' }}">
                                {{ $bookingInfo->city }}{{ !empty($bookingInfo->state) ? ', ' . $bookingInfo->state : '' }}{{ !empty($bookingInfo->country) ? ', ' . $bookingInfo->country : '' }}
                            </p>

                            <p class="text-line {{ $language->direction == 1 ? 'rtl' : '' }}">{{ FullDateTimeInvoice($bookingInfo->event_date) }}</p>

                            <div class="divider"></div>
                            <p class="ticket-name {{ $language->direction == 1 ? 'rtl' : '' }}">{{ $ticketItem['label'] }}</p>

                            @if ($ticketItem['slot_name'] || $ticketItem['seat_name'])
                                <p class="text-line {{ $language->direction == 1 ? 'rtl' : '' }}">
                                    @if (!empty($ticketItem['slot_name']))
                                        {{ __('Slot') }}: {{ $ticketItem['slot_name'] }}
                                    @endif
                                    @if (!empty($ticketItem['slot_name']) && !empty($ticketItem['seat_name']))
                                        |
                                    @endif
                                    @if (!empty($ticketItem['seat_name']))
                                        {{ __('Seat') }}: {{ $ticketItem['seat_name'] }}
                                    @endif
                                </p>
                            @endif

                            <div class="divider"></div>
                            <p class="label {{ $language->direction == 1 ? 'rtl' : '' }}">{{ __('Billing Address') }}</p>
                            <p class="value-regular {{ $language->direction == 1 ? 'rtl' : '' }}">{{ $billingAddress !== '' ? $billingAddress : '-' }}</p>

                            <div class="divider"></div>
                            <table class="grid">
                                <tr>
                                    <td>
                                        <p class="label">{{ __('Booking Date') }}</p>
                                        <p class="value">{{ date_format($bookingInfo->created_at, 'M d, Y') }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Duration') }}</p>
                                        <p class="value">{{ !empty($duration) ? $duration : '-' }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Booking ID') }}</p>
                                        <p class="value">#{{ $bookingInfo->booking_id }}</p>
                                    </td>
                                </tr>
                            </table>

                            <div class="divider"></div>
                            <table class="grid">
                                <tr>
                                    <td>
                                        <p class="label">{{ __('Tax') }}</p>
                                        <p class="value">{{ $formatMoney($bookingInfo->tax ?? 0) }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Early Bird') }}</p>
                                        <p class="value">{{ $formatMoney($bookingInfo->early_bird_discount ?? 0) }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Coupon') }}</p>
                                        <p class="value">{{ $formatMoney($bookingInfo->discount ?? 0) }}</p>
                                    </td>
                                </tr>
                            </table>

                            <div class="divider"></div>
                            <table class="grid">
                                <tr>
                                    <td>
                                        <p class="label">{{ __('Total Paid') }}</p>
                                        <p class="value">{{ $formatMoney(($bookingInfo->price ?? 0) + ($bookingInfo->tax ?? 0)) }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Payment Method') }}</p>
                                        <p class="value">{{ $paymentMethodLabel }}</p>
                                    </td>
                                    <td>
                                        <p class="label">{{ __('Payment Status') }}</p>
                                        <p class="value">{{ $paymentStatusLabel }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td class="right-col">
                            @if (!empty($ticketItem['qr']))
                                <img src="{{ $ticketItem['qr'] }}" alt="qr-code" class="qr-image">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if (!empty($event->instructions))
                <div class="info-box">
                    {!! $event->instructions !!}
                </div>
            @endif
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @empty
        <div class="page-wrap">
            <div class="ticket-card">
                <p>{{ __('No ticket data found for this booking.') }}</p>
            </div>
        </div>
    @endforelse
</body>

</html>
