@extends('pdfs.layouts.master')
@section('title', 'Reporte Clínico – {{ $clinica['nombre'] }}')

@section('content')
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo_base64']))
            <img src="{{ $clinica['logo_base64'] }}" alt="Logo">
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
@endsection
