@extends('pdfs.layouts.master')
@section('title', 'Historia Clínica – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
{{-- ENCABEZADO --}}
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
        <div class="doc-sub">N.° HC-{{ str_pad($paciente->id, 6, '0', STR_PAD_LEFT) }}</div>
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
            <th style="width:115px">Especialista</th>
            <th>Descripción / Diagnóstico</th>
            <th style="width:115px;text-align:center">Odontograma</th>
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
                    <span style="font-family:Arial,sans-serif;font-size:8.5pt;color:#64748b">{{ $evo->created_at->format('H:i') }}</span>
                </td>
                <td>{{ $evo->dentist->name ?? 'Especialista' }}</td>
                <td style="white-space:pre-wrap;font-size:11pt;">{{ $evo->description }}</td>
                <td style="text-align:center">
                    @if($modificadas > 0)
                        <span class="badge badge-green">&#10004; Actualizado</span>
                        <br><span style="font-family:Arial,sans-serif;font-size:8pt;color:#64748b">({{ $modificadas }} piezas)</span>
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
            <th style="width:100px">Comprobante</th>
            <th class="right" style="width:90px">Importe (S/)</th>
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
            <td colspan="4" style="text-align:left;font-family:Arial,sans-serif;font-size:9.5pt;color:#475569;font-weight:normal;">Total pagado en el período</td>
            <td class="text-right color-green fw-bold">S/ {{ number_format($totalPagos, 2) }}</td>
        </tr>
    </tfoot>
</table>
@endif

<div class="legal-note">
    Este documento tiene validez informativa y es de uso médico exclusivo.
    La información contenida es confidencial conforme a la Ley N.° 29733 – Ley de Protección de Datos Personales.
</div>
@endsection
