<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $notification->judul }}</title>
</head>

<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td
                            style="background:linear-gradient(135deg,#1d4ed8,#2563eb);padding:30px;color:white;text-align:center;">
                            <h1 style="margin:0;font-size:28px;letter-spacing:1px;">
                                SIGAP
                            </h1>
                            <p style="margin:8px 0 0;font-size:14px;opacity:.9;">"
                                Sistem Informasi Kepegawaian
                            </p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:35px;">
                            <!-- Judul -->
                            <div
                                style="background:#eff6ff;border-left:5px solid #2563eb;padding:15px 20px;margin-bottom:25px;border-radius:8px;">
                                <h2 style="margin:0;color:#1e40af;font-size:20px;">
                                    {{ $notification->judul }}
                                </h2>
                                <p style=" margin:8px 0 0; color:#64748b; font-size:13px;">
                                    {{ $notification->tanggal_notifikasi->translatedFormat('d F Y H:i') }}
                                </p>
                            </div>
                            <!-- Pesan -->
                            <div style="color:#334155;font-size:15px;line-height:1.8;">
                                {!! nl2br(e($notification->pesan)) !!}
                            </div>
                            <!-- Informasi -->
                            <div
                                style="margin-top:30px;padding:15px;background:#f8fafc;border-radius:8px;color:#64748b;font-size:13px;">
                                Email ini dikirim secara otomatis oleh sistem
                                <strong>SIGAP</strong>.
                                <br>
                                Mohon tidak membalas email ini.
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#0f172a;color:#cbd5e1;text-align:center;padding:20px;font-size:13px;">
                            <strong style="color:#ffffff;">
                                SIGAP
                            </strong>
                            <br>
                            Sistem Informasi Kepegawaian
                            <br><br>
                            © {{ date('Y') }} - Semua hak dilindungi.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
