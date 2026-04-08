<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            $logoDataUri = '';
            $path = public_path('images/logo.png');
            if (is_file($path)) {
                $binary = file_get_contents($path);
                if ($binary !== false) {
                    $mime = function_exists('mime_content_type')
                        ? (mime_content_type($path) ?: 'image/png')
                        : 'image/png';
                    $logoDataUri = 'data:'.$mime.';base64,'.base64_encode($binary);
                }
            }

            return (new MailMessage)
                ->subject('Bevestig je e-mailadres')
                ->view('emails.verify-email', [
                    'verifyUrl' => $url,
                    'logoDataUri' => $logoDataUri,
                ]);
        });
    }
}
