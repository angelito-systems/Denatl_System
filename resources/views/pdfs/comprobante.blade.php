<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago - {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .clinica-nombre { font-size: 24px; font-weight: bold; }
        .clinica-datos { font-size: 12px; color: #666; }
        .comprobante-info { float: right; border: 1px solid #333; padding: 10px; text-align: center; width: 200px; }
        .clear { clear: both; margin-bottom: 20px; }
        .datos-cliente { border: 1px solid #e5e7eb; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; }
        .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>

<div>
    <div style="float: left; width: 60%;" class="header">
        <div class="clinica-nombre" style="text-align: left;">{{ $clinica['nombre'] }}</div>
        <div class="clinica-datos" style="text-align: left;">
            @if($clinica['ruc']) RUC: {{ $clinica['ruc'] }}<br>@endif
            @if($clinica['telefono']) Teléfono: {{ $clinica['telefono'] }}<br>@endif
            {{ $clinica['direccion'] }}
        </div>
    </div>
    <div class="comprobante-info">
        <strong style="font-size: 18px;">RECIBO DE PAGO</strong><br>
        N° {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}
    </div>
</div>
<div class="clear"></div>

<div class="datos-cliente">
    <strong>Fecha:</strong> {{ $pago->created_at->format('d/m/Y H:i') }}<br>
    <strong>Paciente:</strong> {{ $pago->patient ? $pago->patient->first_name . ' ' . $pago->patient->last_name : '---' }}<br>
    <strong>DNI:</strong> {{ $pago->patient->dni ?? '---' }}<br>
    <strong>Método de Pago:</strong> {{ $pago->payment_method }}
</div>

<table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th style="text-align: right;">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Abono por tratamientos odontológicos / Notas: {{ $pago->notes ?? 'Sin detalle adicional' }}</td>
            <td style="text-align: right;">S/ {{ number_format($pago->amount, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td style="text-align: right;">IMPORTE TOTAL PAGADO</td>
            <td style="text-align: right;">S/ {{ number_format($pago->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    {{ $clinica['nombre'] }} - Este documento es un comprobante de control interno.<br>
    ¡Gracias por su preferencia!
</div>

</body>
</html>
