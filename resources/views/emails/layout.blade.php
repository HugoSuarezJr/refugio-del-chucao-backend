@php
    $frontendUrl = rtrim((string) config('services.stripe.frontend_url', config('app.url')), '/');
    $logoUrl = $frontendUrl !== '' ? "{$frontendUrl}/apple-touch-icon.png" : null;
    $supportEmail = config('mail.from.address');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Refugio del Chucao' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f6f1ea; color:#263238;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:linear-gradient(180deg, #ede4d5 0%, #f6f1ea 220px);">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;">
                    <tr>
                        <td style="padding-bottom:20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                @if($logoUrl)
                                                    <td style="padding-right:14px; vertical-align:middle;">
                                                        <img src="{{ $logoUrl }}" alt="Refugio del Chucao" width="56" height="56" style="display:block; width:56px; height:56px; border-radius:16px; border:1px solid rgba(42, 94, 71, 0.16); box-shadow:0 12px 24px rgba(42, 94, 71, 0.12);">
                                                    </td>
                                                @endif
                                                <td style="vertical-align:middle;">
                                                    <div style="font-family: Georgia, 'Times New Roman', serif; font-size:28px; line-height:1.1; font-weight:700; color:#214a38;">
                                                        Refugio del Chucao
                                                    </div>
                                                    <div style="font-family: Arial, sans-serif; font-size:12px; line-height:1.4; letter-spacing:1.6px; text-transform:uppercase; color:#6f6a61; padding-top:6px;">
                                                        Llanada Grande, Chile
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#fffdf9; border:1px solid #e3d8c8; border-radius:28px; overflow:hidden; box-shadow:0 20px 40px rgba(35, 57, 48, 0.08);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:40px 36px 12px; background:linear-gradient(135deg, #214a38 0%, #2f6a4d 100%);">
                                        @if(!empty($eyebrow))
                                            <div style="font-family: Arial, sans-serif; font-size:12px; line-height:1.4; letter-spacing:2px; text-transform:uppercase; color:#d9e6df; padding-bottom:14px;">
                                                {{ $eyebrow }}
                                            </div>
                                        @endif
                                        <div style="font-family: Georgia, 'Times New Roman', serif; font-size:34px; line-height:1.15; font-weight:700; color:#f7f0e7;">
                                            {{ $headline ?? 'Refugio del Chucao' }}
                                        </div>
                                        @if(!empty($subheadline))
                                            <div style="font-family: Arial, sans-serif; font-size:16px; line-height:1.7; color:#e8efe9; padding-top:14px;">
                                                {{ $subheadline }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:28px 36px 36px; font-family: Arial, sans-serif; font-size:16px; line-height:1.75; color:#334155;">
                                        {!! $slot !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 12px 0; text-align:center; font-family: Arial, sans-serif; font-size:12px; line-height:1.7; color:#7b746a;">
                            Refugio del Chucao
                            @if($supportEmail)
                                · {{ $supportEmail }}
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
