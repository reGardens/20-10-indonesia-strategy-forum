<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Alamat Email — Indonesia Strategy Forum</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; -webkit-font-smoothing:antialiased; color:#1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f5f7; padding:36px 16px;">
        <tr>
            <td align="center">
                <!-- Main Container (Max 600px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <!-- Header Dark Navy with ISF Brand -->
                    <tr>
                        <td style="background-color:#0B1220; padding:28px 32px; border-bottom:3px solid #E10600;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <div style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; font-family:'Montserrat',-apple-system,sans-serif;">
                                            INDONESIA STRATEGY FORUM
                                        </div>
                                        <div style="color:#F87171; font-size:11px; margin-top:4px; font-weight:600; letter-spacing:0.8px; text-transform:uppercase;">
                                            PORTAL ANGGOTA &amp; KEMITRAAN STRATEGIS
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px 32px 28px 32px;">
                            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700; color:#0f172a; line-height:1.4;">
                                Aktivasi Akun Anggota Anda
                            </h1>

                            <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#334155;">
                                Yth. <strong>{{ $userName }}</strong>,
                            </p>

                            <p style="margin:0 0 20px 0; font-size:14px; line-height:1.6; color:#334155;">
                                Terima kasih telah mendaftarkan diri di <strong>Indonesia Strategy Forum (ISF)</strong>. Untuk menyelesaikan pendaftaran dan mengaktifkan akun Portal Anggota Anda, silakan verifikasi alamat email Anda melalui tombol di bawah ini:
                            </p>

                            <!-- Primary CTA Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                                <tr>
                                    <td align="center" style="border-radius:6px; background-color:#E10600;">
                                        <a href="{{ $verificationUrl }}"
                                           target="_blank"
                                           style="display:inline-block; padding:14px 32px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none; text-transform:uppercase; letter-spacing:0.5px; border-radius:6px; background-color:#E10600;">
                                            Verifikasi Email Saya &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry & Security Notice Box -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f8fafc; border-left:4px solid #0B1220; border-radius:4px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <div style="font-size:12.5px; line-height:1.5; color:#475569;">
                                            ⏱ <strong>Masa Berlaku:</strong> Tautan verifikasi ini berlaku selama <strong>{{ $expiresInHours }} jam</strong>. Jika tidak diverifikasi dalam waktu tersebut, Anda dapat meminta tautan verifikasi baru.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback Plain URL -->
                            <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f1f5f9;">
                                <p style="margin:0 0 8px 0; font-size:12px; color:#64748b; line-height:1.5;">
                                    Jika Anda mengalami kesulitan mengklik tombol di atas, salin dan tempel URL berikut ke peramban (browser) Anda:
                                </p>
                                <p style="margin:0; font-size:11.5px; color:#E10600; word-break:break-all; line-height:1.4;">
                                    <a href="{{ $verificationUrl }}" style="color:#E10600; text-decoration:underline;">{{ $verificationUrl }}</a>
                                </p>
                            </div>

                            <p style="margin:24px 0 0 0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                <em>Catatan: Jika Anda tidak pernah mendaftar akun di Indonesia Strategy Forum, silakan abaikan email ini. Tidak ada tindakan lanjutan yang diperlukan.</em>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#0B1220; padding:20px 32px; text-align:center; border-top:1px solid #1E293B;">
                            <div style="color:#94a3b8; font-size:11px; line-height:1.5;">
                                &copy; {{ date('Y') }} <strong>Indonesia Strategy Forum (ISF)</strong>. Seluruh hak cipta dilindungi.
                            </div>
                            <div style="color:#64748b; font-size:10.5px; margin-top:4px;">
                                Riset Kebijakan &bull; Strategi Nasional &bull; Forum Pemikiran Terkurasi
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

