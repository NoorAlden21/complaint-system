<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code)
    {
        // $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Email Verification Code')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('Use the following code to verify your email address:')
            ->line('**' . $this->code . '**')
            ->line('This code will expire in 1 hour.')
            ->line('If you did not create an account, you can ignore this email.');
    }
}
