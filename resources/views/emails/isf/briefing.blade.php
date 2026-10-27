{{--
    ISF Email Briefing (Kerangka Surel) — email-safe template.
    Table-based layout, inline CSS only, no JS, no external CSS. Only image: the ISF logo (absolute asset() URL).
    Preview only (route /anggota/briefing/preview): not sent, no scheduler, no mail config.
--}}
@php
    $ringkasanData = $briefing['ringkasan']['data'] ?? [];
    $maxNilai = count($ringkasanData) ? max(array_column($ringkasanData, 'nilai')) : 0;
    $font = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Briefing Anggota · Indonesia Strategy Forum</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; }
        }
        .action-bar {
            background: #0B1220;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #E10600;
            position: sticky;
            top: 0;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .action-bar button, .action-bar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .btn-copy {
            background: #1f2937;
            color: #f9fafb;
            border-color: #374151 !important;
        }
        .btn-copy:hover {
            background: #374151;
        }
        .btn-print {
            background: #1f2937;
            color: #f9fafb;
            border-color: #374151 !important;
        }
        .btn-print:hover {
            background: #374151;
        }
        .btn-send {
            background: #E10600;
            color: #ffffff;
        }
        .btn-send:hover {
            background: #b90500;
        }
        .btn-back {
            background: transparent;
            color: #9ca3af;
            border-color: #374151 !important;
        }
        .btn-back:hover {
            color: #ffffff;
            background: #1f2937;
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;">
    <div class="action-bar no-print">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="/cms/email-briefing" class="btn-back">
                ← Kembali ke CMS
            </a>
            <span style="font-size:14px;font-weight:bold;color:#f9fafb;">Pratinjau Email Briefing Eksekutif</span>
            <span style="font-size:11px;background:#374151;color:#d1d5db;padding:3px 8px;border-radius:9999px;">Siap Kirim</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <button type="button" class="btn-copy" onclick="copyEmailHtml()">
                📋 Salin HTML Surel
            </button>
            <button type="button" class="btn-print" onclick="window.print()">
                🖨️ Cetak / Unduh PDF
            </button>
            <button type="button" class="btn-send" onclick="sendTestBriefing()">
                🚀 Kirim Uji Coba Email
            </button>
        </div>
    </div>

    <table id="email-table-container" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table id="email-content" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;{{ $font }}color:#171717;">

                    {{-- (1) Header --}}
                    <tr>
                        <td bgcolor="#0B1220" style="background:#0B1220;padding:24px 32px;border-bottom:4px solid #E10600;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="64" valign="middle" style="padding:0 16px 0 0;">
                                        <img src="{{ asset('images/isf/logo-isf.jpeg') }}" width="64" height="64" alt="Indonesia Strategy Forum" style="display:block;border:0;border-radius:8px;">
                                    </td>
                                    <td valign="middle">
                                        <p style="margin:0;{{ $font }}font-size:18px;font-weight:bold;letter-spacing:2px;color:#F9FAFB;">INDONESIA STRATEGY FORUM</p>
                                        <p style="margin:8px 0 0;{{ $font }}font-size:13px;color:#D1D5DB;">Ringkasan data dikirimkan untuk Anggota</p>
                                        <p style="margin:4px 0 0;{{ $font }}font-size:13px;color:#D1D5DB;">{{ $briefing['tanggal'] }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0;{{ $font }}font-size:15px;line-height:22px;color:#171717;">
                            Halo {{ $briefing['penerima'] }} ({{ $briefing['organisasi'] }}),
                        </td>
                    </tr>

                    {{-- (2) Ringkasan Data Anggota --}}
                    <tr>
                        <td style="padding:24px 32px 8px;">
                            <h2 style="margin:0 0 16px;{{ $font }}font-size:20px;line-height:26px;color:#171717;">{{ $briefing['ringkasan']['judul'] }}</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                @foreach ($ringkasanData as $item)
                                    @php
                                        $pct = $maxNilai > 0 ? (int) round($item['nilai'] / $maxNilai * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td width="140" style="padding:6px 8px 6px 0;{{ $font }}font-size:14px;color:#171717;">{{ $item['label'] }}</td>
                                        <td style="padding:6px 0;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f5f5;">
                                                <tr>
                                                    @if ($pct > 0)
                                                        <td width="{{ $pct }}%" height="18" bgcolor="#E10600" style="background:#E10600;font-size:0;line-height:0;">&nbsp;</td>
                                                    @endif
                                                    @if ($pct < 100)
                                                        <td height="18" style="font-size:0;line-height:0;">&nbsp;</td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="40" align="right" style="padding:6px 0 6px 8px;{{ $font }}font-size:14px;font-weight:bold;color:#171717;">{{ $item['nilai'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                            <p style="margin:12px 0 0;{{ $font }}font-size:13px;line-height:20px;color:#525252;">{{ $briefing['ringkasan']['catatan'] }}</p>
                        </td>
                    </tr>

                    {{-- (3) Poin-Poin Utama --}}
                    <tr>
                        <td style="padding:24px 32px 8px;">
                            <h2 style="margin:0 0 16px;{{ $font }}font-size:20px;line-height:26px;color:#171717;">Poin-Poin Utama</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td height="160" align="center" bgcolor="#e5e5e5" style="background:#e5e5e5;{{ $font }}font-size:13px;color:#525252;padding:16px;">
                                        Area gambar: infografik ringkasan regulasi pekan ini
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0;{{ $font }}font-size:15px;line-height:22px;color:#171717;">{{ $briefing['pembuka'] }}</p>
                            @foreach ($briefing['poin'] as $index => $poin)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-left:4px solid #E10600;margin:0 0 16px;">
                                    <tr>
                                        <td style="padding:4px 0 4px 16px;">
                                            <h3 style="margin:0 0 6px;{{ $font }}font-size:16px;line-height:22px;color:#171717;">{{ $index + 1 }}. {{ $poin['judul'] }}</h3>
                                            <p style="margin:0 0 6px;{{ $font }}font-size:14px;line-height:21px;color:#404040;">{{ $poin['deskripsi'] }}</p>
                                            <a href="{{ $poin['url'] }}" style="{{ $font }}font-size:14px;font-weight:bold;color:#B30500;text-decoration:underline;">Baca selengkapnya</a>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                            <p style="margin:8px 0 20px;{{ $font }}font-size:15px;line-height:22px;color:#171717;">{{ $briefing['penutup'] }}</p>
                            {{-- Bulletproof button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#E10600" style="background:#E10600;border-radius:6px;">
                                        <a href="{{ $briefing['ctaUrl'] }}" style="display:inline-block;padding:12px 24px;{{ $font }}font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">Ringkasan Data Anggota</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- (4) Tautan Langsung --}}
                    <tr>
                        <td style="padding:24px 32px;">
                            <h2 style="margin:0 0 16px;{{ $font }}font-size:20px;line-height:26px;color:#171717;">Tautan Langsung</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td height="100" align="center" bgcolor="#e5e5e5" style="background:#e5e5e5;{{ $font }}font-size:13px;color:#525252;padding:16px;">
                                        Area gambar: banner tautan langsung anggota
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:12px;">
                                @foreach ($briefing['tautan'] as $tautan)
                                    <tr>
                                        <td style="padding:8px 0;border-bottom:1px solid #e5e5e5;{{ $font }}font-size:14px;">
                                            <a href="{{ $tautan['url'] }}" style="color:#B30500;font-weight:bold;text-decoration:underline;">{{ $tautan['label'] }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 32px 24px;background:#fafafa;{{ $font }}font-size:12px;line-height:18px;color:#525252;">
                            Pratinjau surel — tidak dikirim. Data contoh.<br>
                            © 2026 Indonesia Strategy Forum · indonesiastrategyforum.co.id
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <script>
        function copyEmailHtml() {
            var emailTable = document.getElementById('email-content');
            if (!emailTable) return;
            var html = emailTable.outerHTML;
            navigator.clipboard.writeText(html).then(function() {
                alert('Kode HTML Surel berhasil disalin ke clipboard! Anda dapat menempelkannya (paste) ke platform email marketing seperti Mailchimp, Brevo, atau Outlook.');
            }).catch(function(err) {
                alert('Gagal menyalin HTML: ' + err);
            });
        }
        function sendTestBriefing() {
            var email = prompt('Masukkan alamat email tujuan untuk uji coba pengiriman briefing:', 'analis@indonesiastrategyforum.co.id');
            if (email && email.trim()) {
                alert('Simulasi Pengiriman Sukses!\n\nEmail Briefing eksekutif telah disimulasikan terkirim ke: ' + email + '.\nKonfigurasi SMTP siap disambungkan via mail provider produksi.');
            }
        }
    </script>
</body>
</html>
