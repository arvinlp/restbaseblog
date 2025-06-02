<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class VerificationCodeMail extends Mailable
{
    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Your Verification Code')
            ->view('emails.verification-code')
            ->with(['code' => $this->code]);
    }

    public function toMail($notifiable)
    {
        return (new VerificationCodeMail($this->code))
            ->to($notifiable->email);
    }
}
