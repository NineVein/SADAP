<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:24px;background:#f7f8f3;font-family:Arial,Helvetica,sans-serif;color:#172b25;">
    <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #e2e4d8;border-radius:12px;padding:28px;">
        <h1 style="margin:0 0 12px;font-size:18px;">{{ config('app.name') }}</h1>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Halo {{ $user->name }},<br>
            @if ($tujuan === \App\Services\TwoFactorService::TUJUAN_AKTIVASI)
                gunakan kode berikut untuk mengaktifkan verifikasi dua langkah pada akun Anda:
            @else
                gunakan kode berikut untuk menyelesaikan proses masuk ke akun Anda:
            @endif
        </p>
        <p style="margin:0 0 16px;text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;font-family:'Courier New',monospace;">{{ $kode }}</p>
        <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#4a5345;">
            Kode berlaku {{ $menit }} menit dan hanya bisa dipakai sekali.
        </p>
        <p style="margin:0;font-size:13px;line-height:1.6;color:#9a2b2b;">
            Jangan berikan kode ini kepada siapa pun, termasuk yang mengaku petugas. Bila Anda tidak sedang mencoba masuk,
            abaikan email ini dan segera ganti password Anda.
        </p>
    </div>
</body>
</html>
