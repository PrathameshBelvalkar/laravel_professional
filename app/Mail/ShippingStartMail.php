<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippingStartMail extends Mailable
{
    use Queueable, SerializesModels;

    public $placeholders;
    public $values;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $placeholders, array $values)
    {
        $this->placeholders = $placeholders;
        $this->values = $values;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Shipment is on the Way!')
                    ->view('mail-templates.shipping_start')
                    ->with([
                        'placeholders' => $this->placeholders,
                        'values' => $this->values,
                    ]);
    }
}
