<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Permintaan Kemitraan — Indonesia Strategy Forum</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; -webkit-font-smoothing:antialiased; color:#1e293b;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f5f7; padding:32px 16px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0B1220; padding:28px 32px; border-bottom:3px solid #E10600;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <span style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; font-family:'Montserrat',-apple-system,sans-serif;">
                                            INDONESIA STRATEGY FORUM
                                        </span>
                                        <div style="color:#F87171; font-size:12px; margin-top:4px; font-weight:600; letter-spacing:0.5px;">
                                            DIVISI IKLAN &amp; KEMITRAAN STRATEGIS
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px 32px 24px 32px;">
                            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700; color:#0f172a; line-height:1.4;">
                                Permintaan Kemitraan Anda Telah Kami Terima
                            </h1>

                            <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#334155;">
                                Yth. <strong>{{ $proposal->pic_name }}</strong>,<br>
                                Perwakilan dari <strong>{{ $proposal->institution_name }}</strong>,
                            </p>

                            <p style="margin:0 0 20px 0; font-size:14px; line-height:1.6; color:#334155;">
                                Terima kasih atas ketertarikan Anda untuk berkolaborasi dengan <strong>Indonesia Strategy Forum (ISF)</strong>. Formulir Permintaan Kemitraan (RFP) Anda telah berhasil tercatat dalam sistem kami.
                            </p>

                            <!-- Alert Box 1x24 Hours -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fef2f2; border-left:4px solid #E10600; border-radius:4px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <div style="font-size:13px; font-weight:700; color:#991b1b; margin-bottom:4px;">
                                            ⏱ Waktu Respons Cepat
                                        </div>
                                        <div style="font-size:13px; line-height:1.5; color:#7f1d1d;">
                                            Tim Kemitraan &amp; Pengembangan Bisnis ISF akan meninjau kebutuhan Anda dan menghubungi Anda kembali dalam kurun waktu <strong>1x24 jam kerja</strong> (melalui WhatsApp atau Email) untuk mendiskusikan format kolaborasi yang paling sesuai.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Proposal Details Summary -->
                            <div style="font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">
                                Ringkasan Data Kemitraan
                            </div>

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px; font-size:13px;">
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; width:160px; vertical-align:top;">Lembaga / Perusahaan:</td>
                                    <td style="padding:6px 0; color:#0f172a; font-weight:600;">{{ $proposal->institution_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; vertical-align:top;">Nama Kontak (PIC):</td>
                                    <td style="padding:6px 0; color:#0f172a; font-weight:600;">{{ $proposal->pic_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; vertical-align:top;">Email:</td>
                                    <td style="padding:6px 0; color:#0f172a;">{{ $proposal->email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; vertical-align:top;">No. Telepon / WA:</td>
                                    <td style="padding:6px 0; color:#0f172a;">{{ $proposal->phone }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; vertical-align:top;">Jenis Kemitraan:</td>
                                    <td style="padding:6px 0; color:#E10600; font-weight:700;">{{ $proposal->partnership_type_label }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#64748b; vertical-align:top;">Pesan / Brief:</td>
                                    <td style="padding:6px 0; color:#334155; line-height:1.5; white-space:pre-wrap;">{{ $proposal->message }}</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px 0; font-size:13px; line-height:1.6; color:#64748b;">
                                Apabila Anda memerlukan komunikasi mendesak atau memiliki dokumen pendukung tambahan (misalnya TOR atau Deck Kemitraan), Anda dapat membalas email ini secara langsung.
                            </p>

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:6px; background-color:#0B1220;">
                                        <a href="{{ url('/media-kit') }}" target="_blank" style="display:inline-block; padding:10px 20px; font-size:13px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px;">
                                            Lihat Halaman Media Kit ISF &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:20px 32px; border-top:1px solid #e2e8f0; font-size:11px; line-height:1.5; color:#94a3b8; text-align:center;">
                            <div style="font-weight:600; color:#64748b; margin-bottom:4px;">
                                Indonesia Strategy Forum (ISF)
                            </div>
                            <div>
                                Pusat Analisis Kebijakan Publik, Riset Regulasi, dan Ruang Temu Pemimpin Strategis Nasional.
                            </div>
                            <div style="margin-top:8px;">
                                &copy; {{ date('Y') }} Indonesia Strategy Forum. Hak cipta dilindungi.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

