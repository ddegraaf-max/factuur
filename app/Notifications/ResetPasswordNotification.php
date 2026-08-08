<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Stel je EasyInvoice-wachtwoord opnieuw in')
            ->greeting('Hallo,')
            ->line('Je ontving deze e-mail omdat er een verzoek is gedaan om het wachtwoord van je EasyInvoice-account opnieuw in te stellen.')
            ->action('Wachtwoord opnieuw instellen', $url)
            ->line('Deze link verloopt over 60 minuten.')
            ->line('Heb je dit niet aangevraagd? Dan hoef je niets te doen — je wachtwoord blijft ongewijzigd.')
            ->salutation('Groet, het EasyInvoice-team');
    }
}
