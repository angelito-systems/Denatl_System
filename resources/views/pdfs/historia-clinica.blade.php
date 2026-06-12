<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia Clínica – {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
    <style>
        /* ══ PDF DESIGN SYSTEM v2 ══ */
        @page { margin: 20mm 16mm 24mm 16mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; background: #fff; line-height: 1.65; }

        .header { display: table; width: 100%; padding-bottom: 14px; margin-bottom: 22px; border-bottom: 2.5px solid #0f2040; }
        .header-logo { display: table-cell; width: 72px; vertical-align: middle; }
        .header-logo img { max-width: 64px; max-height: 64px; border-radius: 6px; }
        .header-logo-placeholder { width: 58px; height: 58px; background: #0f2040; border-radius: 8px; font-size: 20px; font-weight: bold; color: #fff; text-align: center; line-height: 58px; letter-spacing: -1px; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .header-clinica { font-size: 17px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 0.8px; }
        .header-meta { font-size: 9.5px; color: #64748b; margin-top: 3px; }
        .header-meta span { margin-right: 14px; }
        .header-doc { display: table-cell; vertical-align: middle; text-align: right; width: 175px; }
        .doc-type-label { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .doc-badge { display: inline-block; background: #0f2040; color: #fff; padding: 5px 14px; border-radius: 4px; font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-sub { font-size: 9.5px; color: #94a3b8; margin-top: 5px; }

        .section-header { display: table; width: 100%; margin: 18px 0 8px; }
        .section-num { display: table-cell; width: 28px; vertical-align: middle; font-size: 9px; font-weight: bold; color: #2563eb; padding-right: 0; text-align: right; }
        .section-bar { display: table-cell; vertical-align: middle; padding-left: 0; }
        .section-bar-inner { background: #0f2040; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; padding: 5px 10px 5px 8px; display: inline-block; }
        .section-line { display: table-cell; border-bottom: 1.5px solid #e2e8f0; vertical-align: bottom; }

        .data-grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data-grid th { background: #f1f5f9; color: #475569; font-size: 9.5px; font-weight: bold; text-transform: uppercase; padding: 7px 10px; border: 1px solid #e2e8f0; width: 140px; white-space: nowrap; }
        .data-grid td { padding: 7px 10px; border: 1px solid #e2e8f0; font-size: 11px; color: #1e293b; }
        .data-grid tr:nth-child(even) td { background: #fafbfd; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11px; }
        .data-table thead tr { background: #0f2040; color: #fff; }
        .data-table thead th { padding: 8px 10px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border: none; text-align: left; }
        .data-table thead th.right { text-align: right; }
        .data-table tbody tr:nth-child(even) td { background: #f8faff; }
        .data-table tbody tr:nth-child(odd)  td { background: #fff; }
        .data-table tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        .data-table tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: bold; }
        .data-table tfoot td { padding: 8px 10px; border-top: 2px solid #0f2040; font-weight: bold; font-size: 11px; background: #f8faff; }
        .empty-row td { text-align: center; color: #94a3b8; padding: 24px; font-style: italic; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        .legal-note { margin-top: 24px; padding: 9px 12px; background: #f8faff; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 9px; color: #94a3b8; text-align: center; line-height: 1.6; }

        .footer { position: fixed; bottom: -17px; left: 0; right: 0; border-top: 1.5px solid #e2e8f0; display: table; width: 100%; padding-top: 6px; background: #fff; }
        .footer-left   { display: table-cell; font-size: 8.5px; color: #94a3b8; vertical-align: middle; }
        .footer-center { display: table-cell; font-size: 8.5px; color: #94a3b8; vertical-align: middle; text-align: center; }
        .footer-right  { display: table-cell; font-size: 8.5px; color: #94a3b8; text-align: right; vertical-align: middle; }

        .text-right  { text-align: right !important; }
        .color-green { color: #059669; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

{{-- FOOTER FIJO --}}
<div class="footer">
    <div class="footer-left">Generado el {{ $fecha }} · {{ $clinica['nombre'] }}</div>
    <div class="footer-center">N° HC-{{ str_pad($paciente->id, 6, '0', STR_PAD_LEFT) }} · Documento Confidencial</div>
    <div class="footer-right">Este documento tiene validez informativa según los términos de la clínica · Uso Médico Exclusivo</div>
</div>

{{-- HEADER --}}
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo']))
            <img src="data:image/png;base64,{{ $clinica['logo'] }}" alt="Logo">
        @else
            <div class="header-logo-placeholder">{{ strtoupper(substr($clinica['nombre'], 0, 2)) }}</div>
        @endif
    </div>
    <div class="header-info">
        <div class="header-clinica">{{ $clinica['nombre'] }}</div>
        <div class="header-meta">
            @if($clinica['ruc'])<span>RUC: <strong>{{ $clinica['ruc'] }}</strong></span>@endif
            @if($clinica['telefono'])<span>Tel: {{ $clinica['telefono'] }}</span>@endif
            @if($clinica['direccion'])<span>{{ $clinica['direccion'] }}</span>@endif
        </div>
    </div>
    <div class="header-doc">
        <div class="doc-type-label">Documento Clínico</div>
        <div class="doc-badge">Historia Clínica</div>
        <div class="doc-sub">N° HC-{{ str_pad($paciente->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>
</div>

{{-- SECCIÓN 01 · DATOS DEL PACIENTE --}}
<div class="section-header">
    <div class="section-num">01</div>
    <div class="section-bar"><div class="section-bar-inner">Datos del Paciente</div></div>
    <div class="section-line"></div>
</div>

<table class="data-grid">
    <tr>
        <th>Apellidos y Nombres</th>
        <td colspan="3"><strong>{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</strong></td>
    </tr>
    <tr>
        <th>DNI / Documento</th>
        <td>{{ $paciente->dni ?? '—' }}</td>
        <th>Género</th>
        <td>{{ $paciente->gender ?? '—' }}</td>
    </tr>
    <tr>
        <th>Fecha de Nacimiento</th>
        <td>{{ $paciente->date_of_birth ? \Carbon\Carbon::parse($paciente->date_of_birth)->format('d/m/Y') : '—' }}</td>
        <th>Edad</th>
        <td>{{ $paciente->date_of_birth ? \Carbon\Carbon::parse($paciente->date_of_birth)->age . ' años' : '—' }}</td>
    </tr>
    <tr>
        <th>Teléfono / Celular</th>
        <td>{{ $paciente->phone ?? '—' }}</td>
        <th>Correo Electrónico</th>
        <td>{{ $paciente->email ?? '—' }}</td>
    </tr>
    @if(!empty($paciente->address))
    <tr>
        <th>Dirección</th>
        <td colspan="3">{{ $paciente->address }}</td>
    </tr>
    @endif
</table>

{{-- SECCIÓN 02 · EVOLUCIÓN CLÍNICA --}}
<div class="section-header">
    <div class="section-num">02</div>
    <div class="section-bar"><div class="section-bar-inner">Evolución Clínica</div></div>
    <div class="section-line"></div>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:80px">Fecha</th>
            <th style="width:110px">Especialista</th>
            <th>Descripción / Diagnóstico</th>
            <th style="width:110px;text-align:center">Odontograma</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paciente->evolutions ?? [] as $evo)
            @php
                $modificadas = (!empty($evo->odontogram_data) && is_array($evo->odontogram_data)) ? count($evo->odontogram_data) : 0;
            @endphp
            <tr>
                <td>
                    <strong>{{ $evo->created_at->format('d/m/Y') }}</strong><br>
                    <span style="font-size:9.5px;color:#64748b">{{ $evo->created_at->format('H:i') }}</span>
                </td>
                <td>{{ $evo->dentist->name ?? 'Especialista' }}</td>
                <td style="white-space:pre-wrap;font-size:11px;">{{ $evo->description }}</td>
                <td style="text-align:center">
                    @if($modificadas > 0)
                        <span class="badge badge-green">✔ Actualizado</span>
                        <br><span style="font-size:9px;color:#64748b">({{ $modificadas }} piezas)</span>
                    @else
                        <span class="badge badge-gray">Sin cambios</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr class="empty-row"><td colspan="4">No existen registros de evolución clínica.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- SECCIÓN 03 · HISTORIAL DE PAGOS --}}
@if($paciente->payments && $paciente->payments->count() > 0)
<div class="section-header">
    <div class="section-num">03</div>
    <div class="section-bar"><div class="section-bar-inner">Historial de Pagos</div></div>
    <div class="section-line"></div>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:75px">Fecha</th>
            <th>Concepto</th>
            <th style="width:75px">Tipo</th>
            <th style="width:95px">Comprobante</th>
            <th class="right" style="width:85px">Importe (S/)</th>
        </tr>
    </thead>
    <tbody>
        @php $totalPagos = 0; @endphp
        @foreach($paciente->payments as $p)
            @php $totalPagos += $p->amount; @endphp
            <tr>
                <td>{{ $p->created_at->format('d/m/Y') }}</td>
                <td>{{ $p->notes ?? 'Pago de tratamiento' }}</td>
                <td>{{ $p->receipt_type ?? '—' }}</td>
                <td>{{ $p->sunat_serie ? $p->sunat_serie.'-'.$p->sunat_correlativo : '—' }}</td>
                <td class="right">{{ number_format($p->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:left;font-size:10px;color:#475569;font-weight:normal;">Total pagado en el período</td>
            <td class="text-right color-green fw-bold">S/ {{ number_format($totalPagos, 2) }}</td>
        </tr>
    </tfoot>
</table>
@endif

<div class="legal-note">
    Este documento tiene validez informativa y es de uso médico exclusivo. La información contenida es confidencial conforme a la Ley N° 29733 – Ley de Protección de Datos Personales.
</div>

</body>
</html>
