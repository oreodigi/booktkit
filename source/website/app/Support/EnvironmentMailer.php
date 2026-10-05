<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

/** Legacy PHPMailer paths must obey the same no-delivery rule as Laravel mail. */
class EnvironmentMailer extends PHPMailer
{
    public function send()
    {
        if (app()->environment(['staging', 'testing'])) {
            Log::info('Non-production email captured; delivery disabled', [
                'subject' => $this->Subject,
                'recipient_count' => count($this->getToAddresses()),
                'attachment_count' => count($this->getAttachments()),
            ]);
            return true;
        }
        return parent::send();
    }
}
