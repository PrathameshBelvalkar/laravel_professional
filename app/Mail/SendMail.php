<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
  use Queueable, SerializesModels;

  public $data;
  public $view;
  public $subject;
  public $attachment;
  public $attachmentName;
  public function __construct($data, $view, $attachment = null,$attachmentName = null)
  {
    $this->subject = $data['subject'];
    $this->data = $data;
    $this->view = $view;
    $this->attachment = $attachment;
    $this->attachmentName = $attachmentName;
    
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->subject,
    );
  }

  public function content(): Content
  {
    return new Content(
      view: $this->view,
      with: ['data' => $this->data]
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
    public function attachments(): array
    {
        if ($this->attachment && $this->attachmentName) {
            return [
                Attachment::fromData(fn() => $this->attachment, $this->attachmentName)
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
