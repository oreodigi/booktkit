<?php

namespace App\Services\Tickets;

use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\EventContent;
use App\Models\Language;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketDeliveryService
{
    public function deliver(Booking $booking): bool
    {
        try {
            if (!in_array($booking->paymentStatus, ['completed', 'free'], true) || empty($booking->email)) return false;
            $tickets = app(TicketIssuanceService::class)->ensureForBooking($booking);
            if (empty($tickets)) return false;

            $qrDir = public_path('assets/admin/qrcodes/');
            $invoiceDir = public_path('assets/admin/file/invoices/');
            @mkdir($qrDir, 0775, true); @mkdir($invoiceDir, 0775, true);
            foreach ($tickets as $ticket) {
                QrCode::size(240)->margin(1)->generate($ticket['token'], $qrDir . 'secure_' . $ticket['uuid'] . '.svg');
            }

            $language = Language::where('is_default', 1)->first();
            $event = Event::findOrFail($booking->event_id);
            $eventInfo = EventContent::where('event_id', $booking->event_id)->where('language_id', $language->id)->first();
            $websiteInfo = Basic::first(); $issuedTickets = $tickets;
            $width = '50%'; $float = 'right'; $mb = '35px'; $ml = '18px';
            $fileName = $booking->booking_id . '.pdf';

            Pdf::loadView('frontend.event.invoice', compact('booking', 'event', 'eventInfo', 'width', 'float', 'mb', 'ml', 'language', 'websiteInfo', 'issuedTickets') + ['bookingInfo' => $booking])
                ->save($invoiceDir . $fileName);
            $booking->invoice = $fileName; $booking->save();
            $this->send($booking, $eventInfo, $event, $invoiceDir . $fileName);
            return true;
        } catch (\Throwable $e) {
            Log::error('Ticket delivery failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            report($e); return false;
        }
    }

    private function send(Booking $booking, $eventInfo, Event $event, string $attachment): void
    {
        $template = MailTemplate::where('mail_type', 'event_booking')->first();
        $info = DB::table('basic_settings')->select('website_title','smtp_status','smtp_host','smtp_port','encryption','smtp_username','smtp_password','from_mail','from_name')->first();
        $subject = $template->mail_subject ?? ('Your BookTkit tickets - ' . ($eventInfo->title ?? 'Event'));
        $body = $template->mail_body ?? '<p>Hello {customer_name},</p><p>Your booking {order_id} is confirmed.</p>';
        $name = trim($booking->fname . ' ' . $booking->lname);
        $body = str_replace(['{customer_name}','{order_id}','{website_title}'], [$name,$booking->booking_id,$info->website_title ?? 'BookTkit'], $body);
        $body = str_replace('{title}', e($eventInfo->title ?? 'Event'), $body);
        $body = str_replace('{meeting_url}', $event->event_type === 'online' ? e((string)$event->meeting_url) : '', $body);
        $ticketCount = app(TicketIssuanceService::class)->ensureForBooking($booking);
        $ticketCount = count($ticketCount);
        $eventDate = $booking->event_date ?: trim(($event->start_date ?? '') . ' ' . ($event->start_time ?? ''));
        $venue = $event->event_type === 'online' ? __('Online event') : trim((string) ($eventInfo->address ?? ''));

        $body .= '<div style="margin:24px 0;padding:18px;border:1px solid #e7e7e7;border-radius:10px;background:#fafafa">'
            . '<h2 style="margin:0 0 12px">Booking confirmed</h2>'
            . '<p style="margin:6px 0"><strong>Event:</strong> ' . e($eventInfo->title ?? 'Event') . '</p>'
            . '<p style="margin:6px 0"><strong>Booking ID:</strong> ' . e($booking->booking_id) . '</p>'
            . '<p style="margin:6px 0"><strong>Booked by:</strong> ' . e($name) . '</p>'
            . '<p style="margin:6px 0"><strong>Tickets:</strong> ' . $ticketCount . '</p>'
            . ($eventDate ? '<p style="margin:6px 0"><strong>Date:</strong> ' . e($eventDate) . '</p>' : '')
            . ($venue ? '<p style="margin:6px 0"><strong>Venue:</strong> ' . e($venue) . '</p>' : '')
            . '</div>';
        $body .= '<p><strong>Your BookTkit ticket PDF is attached.</strong> It contains one ticket and one secure QR credential per attendee. Do not share the QR codes publicly.</p>';
        if (is_numeric($booking->customer_id)) $body .= '<p><a style="display:inline-block;padding:11px 18px;background:#f2b705;color:#111;text-decoration:none;border-radius:6px;font-weight:700" href="' . route('customer.booking_details', $booking->id) . '">View tickets on BookTkit</a></p>';

        $mail = new \App\Support\EnvironmentMailer(true); $mail->CharSet = 'UTF-8'; $mail->Encoding = 'base64';
        if ((int)($info->smtp_status ?? 0) === 1) {
            $mail->isSMTP(); $mail->Host=$info->smtp_host; $mail->SMTPAuth=true;
            $mail->Username=$info->smtp_username; $mail->Password=$info->smtp_password;
            if (($info->encryption ?? '') === 'TLS') $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port=(int)$info->smtp_port;
        }
        $mail->setFrom($info->from_mail, $info->from_name); $mail->addAddress($booking->email, $name);
        $mail->addAttachment($attachment, 'BookTkit-Tickets-' . $booking->booking_id . '.pdf');
        $mail->isHTML(true); $mail->Subject=$subject; $mail->Body=$body; $mail->send();
    }
}
