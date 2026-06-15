@extends('pdfs.layouts.master')
@section('title', 'Contrato de Ortodoncia – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo_base64']))
            <img src="{{ $clinica['logo_base64'] }}" alt="Logo">
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
        <div class="doc-type-label">Especialidad</div>
        <div class="doc-badge">Ortodoncia</div>
        <div class="doc-sub">Fecha: {{ $fecha }}</div>
    </div>
</div>

{{-- TÍTULO --}}
<div class="doc-title">
    <h1>Contrato de Tratamiento de Ortodoncia</h1>
    <div class="subtitle">Acuerdo de tratamiento y compromisos entre el especialista y el paciente</div>
</div>

{{-- DATOS PACIENTE --}}
<div class="info-box">
    <div class="info-box-col" style="width:60%">
        <div class="ib-label">Datos del Paciente</div>
        <div class="ib-name">{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</div>
        <div class="ib-meta">
            DNI: {{ $paciente->dni ?? '—' }}
            @if($paciente->date_of_birth) &nbsp;·&nbsp; Edad: {{ \Carbon\Carbon::parse($paciente->date_of_birth)->age }} años @endif
        </div>
    </div>
    <div class="info-box-col">
        <div class="ib-label">Contacto</div>
        <div class="ib-meta" style="margin-top:4px">
            @if($paciente->phone)Tel: {{ $paciente->phone }}<br>@endif
            @if($paciente->email){{ $paciente->email }}@endif
        </div>
    </div>
</div>

{{-- DESCRIPCIÓN --}}
<div class="section-title">Descripción del Tratamiento</div>
<div class="body-text">
    El tratamiento de ortodoncia tiene como objetivo corregir la posición de los dientes y la relación entre
    los maxilares, mejorando la función masticatoria, la fonación y la estética dental. El profesional tratante
    ha explicado al paciente el diagnóstico, el plan de tratamiento propuesto y la duración estimada.
</div>

{{-- PLAN DE TRATAMIENTO --}}
<div class="section-title">Plan de Tratamiento</div>
<table class="plan-table">
    <thead>
        <tr>
            <th style="width:40%">Descripción</th>
            <th style="width:20%">Inicio Estimado</th>
            <th style="width:20%">Duración Aprox.</th>
            <th class="right" style="width:20%">Costo (S/)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="fill">Completar según evaluación clínica</td>
            <td class="fill">___________</td>
            <td class="fill">___ meses</td>
            <td class="fill right">___________</td>
        </tr>
        <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
    </tbody>
</table>

{{-- COMPROMISOS --}}
<div class="section-title">Compromisos del Paciente</div>
<ul class="check-list">
    <li>Asistir puntualmente a las citas de control y ajuste programadas.</li>
    <li>Mantener una higiene bucal rigurosa y cumplir las indicaciones del profesional.</li>
    <li>Evitar alimentos duros, pegajosos o que puedan dañar el aparato ortodóntico.</li>
    <li>Reportar inmediatamente cualquier molestia inusual o daño en el aparato.</li>
    <li>Cumplir con el cronograma de pagos pactado. El incumplimiento puede derivar en la suspensión del tratamiento.</li>
    <li>Usar los elementos de retención indicados al finalizar el tratamiento activo.</li>
</ul>

{{-- RIESGOS --}}
<div class="section-title">Riesgos y Consideraciones</div>
<div class="body-text">
    El paciente declara haber sido informado sobre los posibles riesgos del tratamiento, incluyendo:
    sensibilidad dental transitoria post-ajuste, riesgo de descalcificación por higiene deficiente, reabsorción
    radicular leve en casos específicos, y la posibilidad de recidiva si no se usa la retención indicada.
    La duración del tratamiento es estimada y puede variar según la respuesta biológica individual.
</div>

{{-- DECLARACIÓN --}}
<div class="declaration">
    <strong>Declaración de conformidad:</strong> En señal de haber leído, entendido y aceptado los términos
    del presente contrato, ambas partes lo suscriben libre y voluntariamente en la ciudad de
    {{ $clinica['ciudad'] ?? 'Lima' }}, el día <strong>{{ $fecha }}</strong>.
</div>

{{-- FIRMAS --}}
<div class="sig-wrap">
    <div class="sig-box">
        <div class="sig-img">
            @if(isset($adminSignature) && $adminSignature)
                <img src="{{ $adminSignature }}" alt="Firma Ortodoncista">
            @endif
        </div>
        <div class="sig-line">
            <div class="sig-name">Ortodoncista Tratante</div>
            <div class="sig-role">{{ $clinica['nombre'] }}</div>
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
