@extends('pdfs.layouts.master')
@section('title', 'Contrato de Implantes Dentales – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo_base64']))
            <img src="{{ $clinica['logo_base64'] }}" alt="Logo">
        @else
            <div class="header-logo-placeholder">{{ strtoupper(substr($clinica['nombre'] ?? 'CD', 0, 2)) }}</div>
        @endif
    </div>
    <div class="header-info">
        <div class="header-clinica">{{ $clinica['nombre'] ?? 'Clínica Dental' }}</div>
        <div class="header-meta">
            @if(!empty($clinica['ruc']))<span>RUC: <strong>{{ $clinica['ruc'] }}</strong></span>@endif
            @if(!empty($clinica['telefono']))<span>Tel: {{ $clinica['telefono'] }}</span>@endif
            @if(!empty($clinica['direccion']))<span>{{ $clinica['direccion'] }}</span>@endif
        </div>
    </div>
    <div class="header-doc">
        <div class="doc-type-label">Especialidad</div>
        <div class="doc-badge">Implantes</div>
        <div class="doc-sub">Fecha: {{ $fecha }}</div>
    </div>
</div>

{{-- TÍTULO --}}
<div class="doc-title">
    <h1>Contrato de Colocación de Implantes Dentales</h1>
    <div class="subtitle">Acuerdo clínico-quirúrgico entre el especialista y el paciente</div>
</div>

{{-- PARTES --}}
<table class="parties-table">
    <tr>
        <td class="lbl">La Clínica</td>
        <td><strong>{{ $clinica['nombre'] ?? 'Clínica Dental' }}</strong>@if(!empty($clinica['ruc'])) &nbsp;·&nbsp; RUC: {{ $clinica['ruc'] }}@endif</td>
    </tr>
    <tr>
        <td class="lbl">El Paciente</td>
        <td><strong>{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</strong> &nbsp;·&nbsp; DNI: {{ $paciente->dni ?? '___________' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha de Suscripción</td>
        <td>{{ $fecha }}</td>
    </tr>
</table>

{{-- CLÁUSULAS --}}
<div class="clause">
    <div class="clause-title">Primero: Objeto del Contrato</div>
    <div class="clause-body">
        El paciente <strong>{{ $paciente->first_name }} {{ $paciente->last_name }}</strong>, identificado con DNI
        <strong>{{ $paciente->dni ?? '___________' }}</strong>, acepta someterse al procedimiento de colocación
        de implantes dentales bajo las condiciones acordadas con el profesional tratante de LA CLÍNICA.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Segundo: Especificaciones del Tratamiento</div>
    <div class="clause-body">
        El profesional tratante ha explicado al paciente los detalles del procedimiento, incluyendo el tipo y
        marca del implante a utilizar, el número de piezas a colocar, las etapas del tratamiento y el tiempo
        estimado de osteointegración. Los detalles adicionales podrán ser consignados en el espacio habilitado
        a continuación o en el anexo clínico correspondiente.
    </div>
</div>

<div class="custom-content">
    <strong>Espacio para detalles adicionales:</strong> El administrador puede consignar aquí información
    específica sobre el material de los implantes, garantías ofrecidas, recomendaciones post-operatorias
    particulares y compromisos de pago complementarios conforme a lo acordado con el paciente.
</div>

<div class="clause">
    <div class="clause-title">Tercero: Obligaciones del Paciente</div>
    <div class="clause-body">
        EL PACIENTE se compromete a cumplir rigurosamente las indicaciones pre y post-operatorias del
        especialista, asistir a los controles programados, mantener una higiene oral adecuada durante el
        período de osteointegración y reportar de inmediato cualquier complicación o molestia inusual.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Cuarto: Riesgos y Garantías</div>
    <div class="clause-body">
        EL PACIENTE declara haber sido informado sobre los riesgos asociados al procedimiento, entre ellos:
        reacción a la anestesia, infección post-quirúrgica, fracaso de la osteointegración y sensibilidad
        transitoria. LA CLÍNICA garantiza la calidad del servicio conforme a los protocolos vigentes, sin que
        ello implique garantía absoluta sobre el resultado, dado que este depende también de factores biológicos
        individuales y del cumplimiento de las indicaciones recibidas.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Quinto: Consentimiento Informado</div>
    <div class="clause-body">
        En señal de conformidad, EL PACIENTE declara haber recibido información clara y suficiente sobre
        el procedimiento propuesto, sus riesgos y alternativas, prestando su consentimiento libre, voluntario
        e informado para la realización del mismo.
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
            <div class="sig-name">Odontólogo / Cirujano Tratante</div>
            <div class="sig-role">{{ $clinica['nombre'] ?? 'Clínica Dental' }}</div>
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
