<?php

namespace App\Modules\Storefront\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Zgłoszenie rekrutacyjne — wiadomość na skonfigurowany adres rekrutacji
 * (config('shop.careers_email')) z CV w załączniku.
 */
class JobApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,email:string,phone:?string,message:?string,position:?string}  $data
     * @param  string|null  $cvAbsolutePath  Pełna ścieżka do zapisanego pliku CV (dysk prywatny).
     * @param  string|null  $cvOriginalName  Oryginalna nazwa pliku CV (do nazwy załącznika).
     */
    public function __construct(
        public array $data,
        public ?string $cvAbsolutePath = null,
        public ?string $cvOriginalName = null,
    ) {}

    public function build(): self
    {
        $shop = config('shop.name');
        $pos = $this->data['position'] ?? null;
        $subject = 'Nowe zgłoszenie rekrutacyjne'
            . ($pos ? ' — ' . $pos : ' (aplikacja spontaniczna)')
            . ' · ' . $this->data['name'];

        $mail = $this->subject($subject)
            // Reply-To na adres kandydata, by można było odpisać bezpośrednio.
            ->replyTo($this->data['email'], $this->data['name'])
            ->view('emails.job-application', [
                'd' => $this->data,
                'shop' => $shop,
            ]);

        if ($this->cvAbsolutePath && is_file($this->cvAbsolutePath)) {
            $mail->attach($this->cvAbsolutePath, [
                'as' => $this->cvOriginalName ?: ('CV-' . $this->data['name'] . '.pdf'),
            ]);
        }

        return $mail;
    }
}
