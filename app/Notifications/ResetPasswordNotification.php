<?php

namespace App\Notifications;

use App\Support\Brand;
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
            ->subject(__('Stel je :brand-wachtwoord opnieuw in', ['brand' => Brand::name()]))
            ->greeting(__('Hallo,'))
            ->line(__('Je ontving deze e-mail omdat er een verzoek is gedaan om het wachtwoord van je :brand-account opnieuw in te stellen.', ['brand' => Brand::name()]))
            ->action(__('Wachtwoord opnieuw instellen'), $url)
            ->line(__('Deze link verloopt over 60 minuten.'))
            ->line(__('Heb je dit niet aangevraagd? Dan hoef je niets te doen — je wachtwoord blijft ongewijzigd.'))
            ->salutation(__('Groet, het :brand-team', ['brand' => Brand::name()]));
    }
}
