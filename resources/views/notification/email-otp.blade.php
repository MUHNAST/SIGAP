<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi SIGAP</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f3f4f6; padding: 32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style=" max-width: 520px; background-color: #ffffff; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background-color: #166534;padding: 28px 24px;text-align: center;color: #ffffff;">
                            <h1 style=" margin: 0; font-size: 26px; letter-spacing: 2px; ">
                                SIGAP
                            </h1>
                            <p style=" margin: 8px 0 0; font-size: 13px; color: #dcfce7;">
                                Sistem Informasi Kenaikan Pangkat,
                                Gaji Berkala, dan Masa Pensiun
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 28px;">
                            <h2 style=" margin: 0 0 16px; font-size: 20px; ">
                                Verifikasi Login
                            </h2>
                            <p style="font-size: 14px; line-height: 1.7;">
                                Halo, {{ $nama }}.
                            </p>
                            <p style="font-size: 14px; line-height: 1.7;">
                                Gunakan kode verifikasi berikut untuk
                                menyelesaikan proses login ke akun SIGAP Anda.
                            </p>
                            <div
                                style="margin: 28px 0; padding: 20px 12px; text-align: center; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                                <p style=" margin: 0 0 10px; font-size: 12px; color: #166534;">
                                    KODE OTP
                                </p>
                                <div style=" font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #166534; ">
                                    {{ $code }}
                                </div>
                            </div>
                            <p style="font-size: 14px; line-height: 1.7;">
                                Kode ini berlaku selama
                                <strong>2 menit</strong>.
                                Jangan bagikan kode ini kepada siapa pun.
                            </p>
                            <p style="font-size: 14px; line-height: 1.7;">
                                Jika Anda tidak merasa melakukan login,
                                abaikan email ini dan segera amankan akun Anda
                                jika diperlukan.
                            </p>
                            <hr style=" margin: 28px 0 20px; border: 0; border-top: 1px solid #e5e7eb;">
                            <p
                                style=" margin: 0; text-align: center; font-size: 12px; line-height: 1.7; color: #6b7280;">
                                Email ini dikirim secara otomatis oleh sistem
                                SIGAP. Mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style=" margin-top: 18px; font-size: 11px; color: #9ca3af; text-align: center;">
                    © {{ date('Y') }} SIGAP
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
