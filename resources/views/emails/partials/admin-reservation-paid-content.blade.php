<p style="margin:0 0 20px;">
    Se registró un pago exitoso en el sitio. La reserva quedó confirmada y ya está bloqueando disponibilidad de forma definitiva.
</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; background-color:#f8f3ea; border:1px solid #e7dccd; border-radius:20px;">
    <tr>
        <td style="padding:24px;">
            <div style="font-family: Georgia, 'Times New Roman', serif; font-size:22px; line-height:1.3; font-weight:700; color:#214a38; padding-bottom:16px;">
                Resumen de la reserva
            </div>

            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:15px; line-height:1.7; color:#374151;">
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Código</td>
                    <td style="padding:6px 0; text-align:right; font-weight:700; color:#214a38;">{{ $reservation->reservation_code }}</td>
                </tr>
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
                    <td style="padding:14px 0 0; color:#214a38; font-weight:700; border-top:1px solid #ddd2c2;">Total</td>
                    <td style="padding:14px 0 0; text-align:right; font-weight:700; color:#214a38; border-top:1px solid #ddd2c2;">
                        ${{ number_format($reservation->total, 0, ',', '.') }} {{ $reservation->currency }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px; background-color:#fffdf9; border:1px solid #ece3d6; border-radius:20px;">
    <tr>
        <td style="padding:24px;">
            <div style="font-family: Georgia, 'Times New Roman', serif; font-size:22px; line-height:1.3; font-weight:700; color:#214a38; padding-bottom:16px;">
                Datos del huésped
            </div>

            <div style="font-size:15px; line-height:1.85; color:#374151;">
                <strong>{{ $reservation->guest_name }}</strong><br>
                {{ $reservation->guest_email }}<br>
                {{ $reservation->guest_phone ?: 'Teléfono no informado' }}
            </div>

            @if($reservation->notes)
                <div style="margin-top:18px; padding-top:18px; border-top:1px solid #ece3d6;">
                    <div style="font-size:13px; text-transform:uppercase; letter-spacing:1.2px; color:#6f6a61; padding-bottom:8px;">
                        Nota del huésped
                    </div>
                    <div style="font-size:15px; color:#374151;">{{ $reservation->notes }}</div>
                </div>
            @endif
        </td>
    </tr>
</table>

<p style="margin:0; color:#4b5563;">
    Recomendación: confirma manualmente cualquier detalle especial de llegada si el huésped dejó notas o teléfono de contacto.
</p>
