<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     *
     * @param array $mailData
     */
    public function __construct($mailData = [])
    {
        $this->mailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->view('mail-templates.mail')
            ->subject($this->mailData['title'] ?? 'No Subject');

        if (!empty($this->mailData['files'])) {
            foreach ($this->mailData['files'] as $file) {
                if (file_exists($file)) {
                    $mail->attach($file);
                }
            }
        }

        if (!empty($this->mailData['attachments'])) {
            foreach ($this->mailData['attachments'] as $attachment) {
                if (isset($attachment['path']) && file_exists(storage_path('app/' . $attachment['path']))) {
                    $mail->attach(storage_path('app/' . $attachment['path']));
                }
            }
        }

        return $mail;
    }
}

