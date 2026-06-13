@extends('pdfs.layouts.master')
@section('title', 'Contrato de Financiamiento – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
@php
    $contrato = $paciente->treatmentContracts()->latest()->first();
@endphp

{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo']))
            <img src="data:image/png;base64,{{ $clinica['logo'] }}" alt="Logo">
        @else
            <div class="header-logo-placeholder">{{ strtoupper(substr($clinica['nombre'],0,2)) }}</div>
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
        <div class="doc-type-label">Contrato Financiero</div>
        <div class="doc-badge">Financiamiento</div>
        <div class="doc-sub">Fecha: {{ $fecha }}</div>
    </div>
</div>

{{-- TÍTULO --}}
<div class="doc-title">
    <h1>Contrato de Prestación de Servicios Odontológicos y Financiamiento</h1>
    <div class="subtitle">Plan de pago acordado entre la clínica y el paciente</div>
</div>

{{-- PARTES --}}
<table class="parties-table">
    <tr>
        <td class="lbl">La Clínica</td>
        <td><strong>{{ $clinica['nombre'] }}</strong>@if($clinica['ruc']) &nbsp;·&nbsp; RUC: {{ $clinica['ruc'] }}@endif</td>
    </tr>
    <tr>
        <td class="lbl">El Paciente</td>
        <td><strong>{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</strong> &nbsp;·&nbsp; DNI: {{ $paciente->dni ?? '___________' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha de Suscripción</td>
        <td>{{ $fecha }}</td>
    </tr>
    @if($contrato)
    <tr>
        <td class="lbl">Tratamiento</td>
        <td>{{ $contrato->treatment_name }}</td>
    </tr>
    @endif
</table>

{{-- RESUMEN FINANCIERO --}}
@if($contrato)
<div class="fin-summary">
    <div class="fin-cell">
        <div class="fin-card">
            <div class="fin-label">Costo Total del Tratamiento</div>
            <div class="fin-value green">S/ {{ number_format($contrato->total_cost, 2) }}</div>
            <div class="fin-sub">Importe total acordado</div>
        </div>
    </div>
    <div class="fin-cell">
        <div class="fin-card blue">
            <div class="fin-label">Cuota Inicial</div>
            <div class="fin-value blue">S/ {{ $contrato->down_payment > 0 ? number_format($contrato->down_payment, 2) : '0.00' }}</div>
            <div class="fin-sub">Pago al inicio del tratamiento</div>
        </div>
    </div>
    <div class="fin-cell">
        <div class="fin-card gold">
            <div class="fin-label">Número de Cuotas</div>
            <div class="fin-value gold">{{ $contrato->installments > 0 ? $contrato->installments : '—' }}</div>
            <div class="fin-sub">Cuotas mensuales</div>
        </div>
    </div>
</div>
@endif

{{-- CLÁUSULAS --}}
<div class="clause">
    <div class="clause-title">Primero: Objeto del Contrato</div>
    <div class="clause-body">
        LA CLÍNICA se compromete a prestar servicios odontológicos especializados a favor de EL PACIENTE,
        específicamente para el tratamiento de <strong>{{ $contrato ? $contrato->treatment_name : 'Tratamiento Odontológico' }}</strong>,
        conforme al plan de tratamiento acordado y registrado en la historia clínica correspondiente.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Segundo: Condiciones Económicas y Financiamiento</div>
    <div class="clause-body">
        Las partes acuerdan que el costo total del tratamiento asciende a la suma de
        <strong>S/ {{ $contrato ? number_format($contrato->total_cost, 2) : '0.00' }}</strong>.
        @if($contrato && $contrato->down_payment > 0)
        EL PACIENTE entregará una cuota inicial de <strong>S/ {{ number_format($contrato->down_payment, 2) }}</strong>.
        @endif
        @if($contrato && $contrato->installments > 0)
        El saldo restante será cancelado en <strong>{{ $contrato->installments }}</strong> cuotas mensuales iguales.
        @endif
        EL PACIENTE se compromete a realizar los pagos de forma puntual según el cronograma acordado.
        El incumplimiento de pago faculta a LA CLÍNICA a suspender temporalmente el tratamiento hasta la regularización de la deuda.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Tercero: Obligaciones del Paciente</div>
    <div class="clause-body">
        EL PACIENTE se compromete a asistir puntualmente a las citas programadas, notificando con
        anticipación cualquier cancelación, y a cumplir estrictamente las indicaciones clínicas del profesional
        tratante a lo largo de todo el proceso.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Cuarto: Consentimiento Informado</div>
    <div class="clause-body">
        EL PACIENTE declara haber sido informado de forma clara sobre la naturaleza, alcances, riesgos y
        alternativas del tratamiento propuesto, prestando su consentimiento libre, voluntario e informado.
    </div>
</div>

{{-- FIRMAS --}}
<div class="sig-wrap">
    <div class="sig-box">
        <div class="sig-img">
            @if(isset($adminSignature) && $adminSignature)
                <img src="{{ $adminSignature }}" alt="Firma Clínica">
            @endif
        </div>
        <div class="sig-line">
            <div class="sig-name">{{ $clinica['nombre'] }}</div>
            <div class="sig-role">Representante Autorizado</div>
        </div>
    </div>
    <div class="sig-box">
        <div class="sig-img">
            @if(isset($signature) && $signature)
                <img src="{{ $signature }}" alt="Firma Paciente">
            @endif
        </div>
        <div class="sig-line">
            <div class="sig-name">{{ $paciente->first_name }} {{ $paciente->last_name }}</div>
            <div class="sig-role">DNI: {{ $paciente->dni ?? '___________' }}</div>
        </div>
    </div>
</div>

<div class="legal-note">
    Este documento tiene validez informativa y contractual según los términos de la clínica.
    La información del paciente es confidencial conforme a la Ley N.° 29733 – Ley de Protección de Datos Personales.
</div>
@endsection
