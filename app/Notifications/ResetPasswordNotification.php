<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $url) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Resetare parolă – Monza Ares Academy')
            ->greeting('Salut,')
            ->line('Am primit o solicitare de resetare a parolei contului tău **Monza Ares Academy**.')
            ->line('Apasă pe butonul de mai jos pentru a alege o nouă parolă. Linkul este valabil **60 de minute**.')
            ->action('Resetează parola', $this->url)
            ->line('Dacă nu ai solicitat resetarea parolei, poți ignora acest email în siguranță — contul tău rămâne nemodificat.')
            ->salutation('Cu stimă, Echipa Monza Ares Academy');
    }
}
