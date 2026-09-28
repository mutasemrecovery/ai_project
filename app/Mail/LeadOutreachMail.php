<?php

namespace App\Mail;

use App\Models\Outreach;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Outreach $outreach)
    {
    }

    public function build(): self
    {
        return $this->subject($this->outreach->subject ?: 'A quick idea for your business')
            ->view('emails.lead_outreach');
    }
}
