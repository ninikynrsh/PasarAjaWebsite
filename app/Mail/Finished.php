<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Finished extends Mailable
{
    use Queueable, SerializesModels;

    private $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this
            ->from(env('MAIL_FROM_ADDRESS', 'hakiahmad756@gmail.com'))
            ->subject('Pesanan ' . $this->data->order_id . ' Telah Selesai')
            ->view('order.finished')
            ->with('data', $this->data); 
    }
}
