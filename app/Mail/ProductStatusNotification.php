<?php

// namespace App\Mail;

// use Illuminate\Bus\Queueable;
// use Illuminate\Mail\Mailable;
// use Illuminate\Queue\SerializesModels;

// class ProductStatusNotification extends Mailable
// {
//     use Queueable, SerializesModels;

//     public $notifData;

//     /**
//      * Create a new message instance.
//      *
//      * @param array $notifData
//      * @return void
//      */
//     public function __construct($notifData)
//     {
//         $this->notifData = $notifData;
//     }

//     /**
//      * Build the message.
//      *
//      * @return $this
//      */
//     public function build()
//     {
//         return $this->view('mail-templates.productStatusNotification')
//                     ->with('notifData', $this->notifData)
//                     ->subject($this->notifData['title']);
//     }
// }

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $notifData;

    /**
     * Create a new message instance.
     *
     * @param array $notifData
     * @return void
     */
    public function __construct(array $notifData)
    {
        $this->notifData = $notifData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Determine the view based on the notification type
        $view = $this->getViewForNotificationType($this->notifData['type']);
        
        return $this->view($view)
                    ->with('notifData', $this->notifData)
                    ->subject($this->notifData['title']);
    }

    /**
     * Get the email view based on the notification type.
     *
     * @param string $type
     * @return string
     */
    protected function getViewForNotificationType($type)
    {
        // Define different views based on the type
        switch ($type) {
            case 'product_status':
                return 'mail-templates.productStatusNotification';
            case 'order_update':
                return 'mail-templates.shipping_start';
            default:
                return 'mail-templates.defaultNotification';
        }
    }
}
