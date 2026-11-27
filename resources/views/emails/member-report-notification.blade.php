<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Baru dari Member ISF</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 24px; color: #1e293b; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background-color: #0B1220; padding: 24px; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; }
        .badge { display: inline-block; background-color: #E10600; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .content { padding: 24px; line-height: 1.6; }
        .meta-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .meta-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .meta-table td.label { width: 140px; font-weight: 600; color: #64748b; }
        .message-box { background: #f8fafc; border-left: 4px solid #E10600; padding: 16px; border-radius: 6px; margin-top: 16px; font-size: 14px; white-space: pre-line; }
        .footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; }
        .btn { display: inline-block; background-color: #0B1220; color: #ffffff !important; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Pengaduan / Laporan Masalah</span>
            <h1>Laporan Baru dari Anggota ISF</h1>
        </div>
        <div class="content">
            <p>Halo Tim Redaksi & Dukungan ISF,</p>
            <p>Seorang anggota terverifikasi baru saja mengirimkan laporan pengaduan/masukan melalui Portal Anggota:</p>

            <table class="meta-table">
                <tr>
                    <td class="label">ID Laporan</td>
                    <td><strong>#{{ $report->id }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Nama Member</td>
                    <td>{{ $report->user?->name ?? 'Anonim' }}</td>
                </tr>
                <tr>
                    <td class="label">Email Member</td>
                    <td>{{ $report->user?->email ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">No. WhatsApp</td>
                    <td>{{ $report->user?->whatsapp ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Kategori</td>
                    <td><strong>{{ $report->category_label }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Subjek Laporan</td>
                    <td><strong>{{ $report->subject }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Waktu Kirim</td>
                    <td>{{ $report->created_at?->format('d M Y H:i') ?? '-' }} WIB</td>
                </tr>
            </table>

            <h4 style="margin-bottom: 6px; font-size: 14px; color: #334155;">Isi Laporan / Keluhan:</h4>
            <div class="message-box">
                {{ $report->message }}
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/cms/laporan-member/' . $report->id) }}" class="btn">
                    Tinjau Laporan di CMS Redaksi
                </a>
            </div>
        </div>
        <div class="footer">
            Pemberitahuan otomatis dari Sistem Portal Anggota Indonesia Strategy Forum.<br>
            Jl. Medan Merdeka Barat, Jakarta Pusat · redaksi@indonesiastrategyforum.id
        </div>
    </div>
</body>
</html>
