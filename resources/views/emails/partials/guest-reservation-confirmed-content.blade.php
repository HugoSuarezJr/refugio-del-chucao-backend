<p style="margin:0 0 18px;">Hola {{ $reservation->guest_name }},</p>

<p style="margin:0 0 24px;">
    Gracias por reservar con nosotros. Tu pago fue procesado correctamente y tu reserva quedó confirmada.
    Guarda este código para cualquier consulta: <strong style="color:#214a38;">{{ $reservation->reservation_code }}</strong>.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; background-color:#f8f3ea; border:1px solid #e7dccd; border-radius:20px;">
    <tr>
        <td style="padding:24px;">
            <div style="font-family: Georgia, 'Times New Roman', serif; font-size:22px; line-height:1.3; font-weight:700; color:#214a38; padding-bottom:16px;">
                Detalles de tu reserva
            </div>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:15px; line-height:1.7; color:#374151;">
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Habitación</td>
                    <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $roomName }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Check-in</td>
                    <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $reservation->check_in?->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Check-out</td>
                    <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $reservation->check_out?->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Huéspedes</td>
                    <td style="padding:6px 0; text-align:right; font-weight:600;">{{ $reservation->number_of_guests }}</td>
                </tr>
                <tr>
                    <td style="padding:14px 0 0; color:#214a38; font-weight:700; border-top:1px solid #ddd2c2;">Total pagado</td>
                    <td style="padding:14px 0 0; text-align:right; font-weight:700; color:#214a38; border-top:1px solid #ddd2c2;">
                        ${{ number_format($reservation->total, 0, ',', '.') }} {{ $reservation->currency }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if($reservation->notes)
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; background-color:#fffdf9; border-left:4px solid #7a8f7d;">
        <tr>
            <td style="padding:16px 18px;">
                <div style="font-size:13px; text-transform:uppercase; letter-spacing:1.2px; color:#6f6a61; padding-bottom:8px;">
                    Tu nota
                </div>
                <div style="font-size:15px; color:#374151;">{{ $reservation->notes }}</div>
            </td>
        </tr>
    </table>
@endif

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; background-color:#214a38; border-radius:20px;">
    <tr>
        <td style="padding:22px 24px; color:#f7f0e7;">
            <div style="font-family: Georgia, 'Times New Roman', serif; font-size:21px; line-height:1.3; font-weight:700; padding-bottom:10px;">
                Próximo paso
            </div>
            <div style="font-size:15px; line-height:1.7; color:#edf3ef;">
                Enviaremos cualquier detalle adicional de llegada y coordinación directamente a este correo.
                @if($contactEmail)
                    Si necesitas ayuda antes de tu viaje, puedes responder a este email o escribirnos a <strong style="color:#ffffff;">{{ $contactEmail }}</strong>.
                @endif
            </div>
        </td>
    </tr>
</table>

<p style="margin:0;">Nos vemos pronto,<br><strong style="color:#214a38;">Equipo Refugio del Chucao</strong></p>
