<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pendaftaran EcoVerse</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 40px; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .header { padding: 20px; border-bottom: 1px solid #e9ecef; }
        .content { padding: 30px; }
        .content p { font-size: 16px; line-height: 1.6; color: #343a40; }
        .button { display: inline-block; padding: 12px 25px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #5DB075; border-radius: 5px; text-decoration: none; margin: 20px 0; }
        .footer { padding: 20px; background-color: #f1f3f5; border-top: 1px solid #e9ecef; font-size: 14px; color: #6c757d; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <strong>Pendaftaran EcoVerse</strong>
        </div>
        <div class="content">
            <p>Halo {{ $name }},</p>
            <p>Terima kasih telah mendaftar di EcoVerse, platform pengelolaan sampah dan komunitas ramah lingkungan. Untuk mengaktifkan akun Anda, silakan klik tombol di bawah ini:</p>

            <a href="{{ $verificationUrl }}" class="button">Verifikasi Email</a>

            <p>Tautan verifikasi ini <strong>berlaku selama 24 jam.</strong><br>Jika Anda tidak merasa membuat akun, abaikan email ini.</p>
            <p>Terima kasih,<br><strong>Tim EcoVerse</strong></p>
        </div>
        <div class="footer">
            <p>Jika Anda memiliki pertanyaan atau kendala, silakan hubungi kami:</p>
            <a href="mailto:support@ecoverse.web.id">support@ecoverse.web.id</a>
        </div>
    </div>
</body>
</html>