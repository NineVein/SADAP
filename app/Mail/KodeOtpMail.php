<?php

namespace App\Mail;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class KodeOtpMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $kode,
        public string $tujuan,
        public int $menit,
    ) {}

    public function envelope(): Envelope
    {
        $judul = $this->tujuan === TwoFactorService::TUJUAN_AKTIVASI
            ? 'Kode aktivasi verifikasi dua langkah'
            : 'Kode masuk';

        return new Envelope(subject: $judul.' - '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.kode-otp');
    }
}
