<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Clínico – {{ $clinica['nombre'] }}</title>
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

        /* PERIODO INFO */
        .periodo-bar { background: #f1f5f9; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; padding: 8px 14px; margin-bottom: 20px; display: table; width: 100%; border-radius: 3px; }
        .periodo-cell { display: table-cell; vertical-align: middle; }
        .periodo-cell + .periodo-cell { border-left: 1px solid #e2e8f0; padding-left: 16px; }
        .periodo-label { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
        .periodo-value { font-size: 11.5px; font-weight: bold; color: #0f2040; margin-top: 1px; }

        /* KPI ROW */
        .kpi-row { display: table; width: 100%; margin-bottom: 24px; }
        .kpi-cell { display: table-cell; width: 33.33%; padding: 0 5px; }
        .kpi-cell:first-child { padding-left: 0; }
        .kpi-cell:last-child  { padding-right: 0; }
        .kpi-card { border: 1px solid #e2e8f0; border-top: 3px solid #2563eb; border-radius: 4px; padding: 13px 14px; background: #fafbff; text-align: center; }
        .kpi-card.green { border-top-color: #059669; }
        .kpi-card.red   { border-top-color: #dc2626; }
        .kpi-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: bold; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #0f2040; margin: 5px 0 1px; }
        .kpi-value.green { color: #059669; }
        .kpi-value.red   { color: #dc2626; }
        .kpi-sub { font-size: 9px; color: #94a3b8; }

        /* SECTION */
        .section-header { display: table; width: 100%; margin: 18px 0 10px; }
        .section-num { display: table-cell; width: 28px; vertical-align: middle; font-size: 9px; font-weight: bold; color: #2563eb; text-align: right; }
        .section-bar { display: table-cell; vertical-align: middle; }
        .section-bar-inner { background: #0f2040; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; padding: 5px 10px 5px 8px; display: inline-block; }
        .section-line { display: table-cell; border-bottom: 1.5px solid #e2e8f0; vertical-align: bottom; }

        /* TABLES */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11px; }
        .data-table thead tr { background: #0f2040; color: #fff; }
        .data-table thead th { padding: 8px 10px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border: none; text-align: left; }
        .data-table thead th.right { text-align: right; }
        .data-table tbody tr:nth-child(even) td { background: #f8faff; }
        .data-table tbody tr:nth-child(odd)  td { background: #fff; }
        .data-table tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        .data-table tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: bold; }
        .empty-row td { text-align: center; color: #94a3b8; padding: 24px; font-style: italic; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }

        .legal-note { margin-top: 24px; padding: 9px 12px; background: #f8faff; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 9px; color: #94a3b8; text-align: center; line-height: 1.6; }

        .footer { position: fixed; bottom: -17px; left: 0; right: 0; border-top: 1.5px solid #e2e8f0; display: table; width: 100%; padding-top: 6px; background: #fff; }
        .footer-left   { display: table-cell; font-size: 8.5px; color: #94a3b8; vertical-align: middle; }
        .footer-center { display: table-cell; font-size: 8.5px; color: #94a3b8; vertical-align: middle; text-align: center; }
        .footer-right  { display: table-cell; font-size: 8.5px; color: #94a3b8; text-align: right; vertical-align: middle; }

        .text-right { text-align: right !important; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">Generado el {{ $fechaEmision }} · {{ $clinica['nombre'] }}</div>
    <div class="footer-center">RUC: {{ $clinica['ruc'] }} · Tel: {{ $clinica['telefono'] }}</div>
    <div class="footer-right">Este documento tiene validez informativa y contractual según los términos de la clínica</div>
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
        </div>
    </div>
    <div class="header-doc">
        <div class="doc-type-label">Informe Administrativo</div>
        <div class="doc-badge">Reporte Clínico</div>
        <div class="doc-sub">Emitido: {{ $fechaEmision }}</div>
    </div>
</div>

{{-- PERIODO Y FILTROS --}}
<div class="periodo-bar">
    <div class="periodo-cell" style="width:55%">
        <div class="periodo-label">Período del Reporte</div>
        <div class="periodo-value">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &nbsp;—&nbsp; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
    </div>
    <div class="periodo-cell">
        <div class="periodo-label">Filtro de Paciente</div>
        <div class="periodo-value">{{ $patientName }}</div>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-card green">
            <div class="kpi-label">Ingresos Recaudados</div>
            <div class="kpi-value green">S/ {{ number_format($totalRevenue, 2) }}</div>
            <div class="kpi-sub">Pagos confirmados en el período</div>
        </div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-card red">
            <div class="kpi-label">Deudas Pendientes</div>
            <div class="kpi-value red">S/ {{ number_format($totalDebts, 2) }}</div>
            <div class="kpi-sub">Saldo por cobrar</div>
        </div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-card">
            <div class="kpi-label">Citas Programadas</div>
            <div class="kpi-value">{{ count($appointments) }}</div>
            <div class="kpi-sub">Registros en el período</div>
        </div>
    </div>
</div>

{{-- SECCIÓN 01 · PAGOS --}}
<div class="section-header">
    <div class="section-num">01</div>
    <div class="section-bar"><div class="section-bar-inner">Ingresos y Pagos Recientes</div></div>
    <div class="section-line"></div>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:90px">Fecha y Hora</th>
            <th>Paciente</th>
            <th style="width:90px">Método de Pago</th>
            <th style="width:75px;text-align:center">Estado</th>
            <th class="right" style="width:85px">Monto (S/)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payments->take(20) as $payment)
        <tr>
            <td>{{ $payment->created_at->format('d/m/Y') }}<br><span style="font-size:9.5px;color:#64748b">{{ $payment->created_at->format('H:i') }}</span></td>
            <td>{{ $payment->patient ? $payment->patient->first_name . ' ' . $payment->patient->last_name : 'N/A' }}</td>
            <td>{{ $payment->payment_method }}</td>
            <td style="text-align:center">
                @if($payment->status === 'Pagado')
                    <span class="badge badge-green">Pagado</span>
                @else
                    <span class="badge badge-red">Pendiente</span>
                @endif
            </td>
            <td class="right">{{ number_format($payment->amount, 2) }}</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="5">No hay pagos registrados en este período.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- SECCIÓN 02 · CITAS --}}
<div style="page-break-inside: avoid;">
    <div class="section-header">
        <div class="section-num">02</div>
        <div class="section-bar"><div class="section-bar-inner">Tratamientos y Citas Recientes</div></div>
        <div class="section-line"></div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:90px">Fecha y Hora</th>
                <th>Paciente</th>
                <th>Tratamiento</th>
                <th style="width:85px;text-align:center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments->take(20) as $appointment)
            <tr>
                <td>{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}<br><span style="font-size:9.5px;color:#64748b">{{ substr($appointment->start_time, 0, 5) }}</span></td>
                <td>{{ $appointment->patient ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name : 'N/A' }}</td>
                <td>{{ $appointment->treatment }}</td>
                <td style="text-align:center"><span class="badge badge-blue">{{ ucfirst($appointment->status) }}</span></td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="4">No hay citas registradas en este período.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="legal-note">
    Este documento tiene validez informativa y contractual según los términos de la clínica. Generado automáticamente por el sistema LifeMedSys · {{ $fechaEmision }}
</div>

</body>
</html>
