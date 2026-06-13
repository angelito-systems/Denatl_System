@extends('pdfs.layouts.master')
@section('title', 'Consentimiento Informado – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
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
        <div class="doc-type-label">Documento Legal</div>
        <div class="doc-badge">Consentimiento</div>
        <div class="doc-sub">Fecha: {{ $fecha }}</div>
    </div>
</div>

{{-- TÍTULO --}}
<div class="doc-title">
    <h1>Consentimiento Informado General</h1>
    <div class="subtitle">Autorización libre y voluntaria para la realización del tratamiento propuesto</div>
</div>

{{-- DATOS PACIENTE --}}
<div class="info-box">
    <div class="info-box-col" style="width:60%">
        <div class="ib-label">Datos del Paciente</div>
        <div class="ib-name">{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</div>
        <div class="ib-meta">
            DNI: {{ $paciente->dni ?? '—' }}
            @if($paciente->date_of_birth) &nbsp;·&nbsp; Nacimiento: {{ \Carbon\Carbon::parse($paciente->date_of_birth)->format('d/m/Y') }} @endif
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

{{-- CUERPO --}}
<div class="section-title">Información sobre el Tratamiento</div>
<div class="body-text">
    El profesional a cargo me ha informado de manera clara y comprensible sobre mi diagnóstico y el
    tratamiento propuesto, incluyendo su naturaleza, objetivos, procedimiento a seguir, beneficios esperados,
    posibles riesgos o complicaciones, y las alternativas disponibles.
</div>

<div class="section-title">Aspectos sobre los que he sido informado</div>
<ul class="check-list">
    <li>Uso de anestesia local y sus posibles efectos transitorios (adormecimiento, molestia leve).</li>
    <li>Posibilidad de inflamación, sensibilidad o leve sangrado post-procedimiento.</li>
    <li>Indicaciones de cuidado post-operatorio que debo seguir rigurosamente.</li>
    <li>Alternativas de tratamiento disponibles y sus diferencias.</li>
    <li>Autorización para tomar fotografías clínicas y radiografías con fines diagnósticos.</li>
    <li>Derecho a revocar este consentimiento en cualquier momento, antes del inicio del procedimiento.</li>
</ul>

<div class="section-title">Preguntas y Aclaraciones</div>
<div class="body-text">
    He tenido la oportunidad de realizar preguntas al profesional tratante, las cuales han sido respondidas
    satisfactoriamente. Comprendo que los resultados del tratamiento pueden variar según mi condición
    biológica individual y el cumplimiento de las indicaciones recibidas.
</div>

{{-- DECLARACIÓN --}}
<div class="declaration">
    <strong>Declaración:</strong> Yo, <strong>{{ $paciente->first_name }} {{ $paciente->last_name }}</strong>,
    identificado(a) con DNI <strong>{{ $paciente->dni ?? '___________' }}</strong>, declaro que la información
    anterior me ha sido explicada de forma satisfactoria y que otorgo mi consentimiento <strong>libre,
    voluntario e informado</strong> para la realización del tratamiento propuesto, en la ciudad de
    {{ $clinica['ciudad'] ?? 'Lima' }}, el día <strong>{{ $fecha }}</strong>.
</div>

{{-- FIRMAS --}}
<div class="sig-wrap">
    <div class="sig-box">
        <div class="sig-img">
            @if(isset($signature) && $signature)
                <img src="{{ $signature }}" alt="Firma Paciente">
            @endif
        </div>
        <div class="sig-line">
            <div class="sig-name">{{ $paciente->first_name }} {{ $paciente->last_name }}</div>
            <div class="sig-role">Paciente &nbsp;·&nbsp; DNI: {{ $paciente->dni ?? '___________' }}</div>
        </div>
    </div>
    <div class="sig-box">
        <div class="sig-img">
            @if(isset($adminSignature) && $adminSignature)
                <img src="{{ $adminSignature }}" alt="Firma Odontólogo">
            @endif
        </div>
        <div class="sig-line">
            <div class="sig-name">Odontólogo Tratante</div>
            <div class="sig-role">{{ $clinica['nombre'] }}</div>
        </div>
    </div>
</div>

<div class="legal-note">
    Este documento tiene validez informativa y contractual según los términos de la clínica.
    La información del paciente es confidencial conforme a la Ley N.° 29733 – Ley de Protección de Datos Personales.
</div>
@endsection
