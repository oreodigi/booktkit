<?php

namespace App\Console\Commands;

use App\Models\Event\Booking;
use App\Services\Tickets\TicketDeliveryService;
use Illuminate\Console\Command;

/**
 * One-off clean-up for files published before secure naming:
 *  - renames every booking's ticket PDF to an unguessable name and updates the booking;
 *  - deletes ticket QR images left in public/assets/admin/qrcodes.
 * Safe to run more than once. Use --dry-run first.
 */
class SecureTicketFiles extends Command
{
    protected $signature = 'tickets:secure-public-files {--dry-run : Show what would change}';
    protected $description = 'Rename public ticket PDFs to unguessable names and remove public QR images';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $dir = public_path('assets/admin/file/invoices/');
        $renamed = 0; $missing = 0;

        Booking::whereNotNull('invoice')->where('invoice', '!=', '')->orderBy('id')->chunkById(200, function ($bookings) use ($dir, $dry, &$renamed, &$missing) {
            foreach ($bookings as $booking) {
                $current = basename((string) $booking->invoice);
                if (preg_match('/-[0-9a-f]{32}\.pdf$/', $current)) continue;
                if (!is_file($dir . $current)) { $missing++; continue; }
                $new = TicketDeliveryService::invoiceFileName($booking);
                if (!$dry) {
                    if (!@rename($dir . $current, $dir . $new)) { $this->warn("Could not rename {$current}"); continue; }
                    Booking::whereKey($booking->id)->update(['invoice' => $new]);
                }
                $renamed++;
            }
        });

        $qrFiles = glob(public_path('assets/admin/qrcodes/*.svg')) ?: [];
        if (!$dry) foreach ($qrFiles as $file) @unlink($file);

        $this->info(($dry ? '[dry run] ' : '') . "Renamed PDFs: {$renamed}; missing PDFs skipped: {$missing}; QR images removed: " . count($qrFiles));
        return self::SUCCESS;
    }
}
