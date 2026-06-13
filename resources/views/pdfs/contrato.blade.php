@extends('pdfs.layouts.master')
@section('title', 'Contrato de Servicios – {{ $paciente->first_name }} {{ $paciente->last_name }}')

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
        <div class="doc-badge">Contrato</div>
        <div class="doc-sub">Fecha: {{ $fecha }}</div>
    </div>
</div>

{{-- TÍTULO --}}
<div class="doc-title">
    <h1>Contrato de Prestación de Servicios Odontológicos</h1>
    <div class="subtitle">Documento legal vinculante entre las partes abajo firmantes</div>
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
</table>

{{-- CLÁUSULAS --}}
<div class="clause">
    <div class="clause-title">Primero: Objeto del Contrato</div>
    <div class="clause-body">
        LA CLÍNICA se compromete a prestar servicios odontológicos especializados a favor de EL PACIENTE,
        conforme al plan de tratamiento acordado y registrado en la historia clínica correspondiente. Los
        tratamientos serán ejecutados por profesionales debidamente colegiados, aplicando protocolos de
        bioseguridad y estándares de calidad vigentes en el sector.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Segundo: Obligaciones del Paciente</div>
    <div class="clause-body">
        EL PACIENTE se compromete a: (a) asistir puntualmente a las citas programadas, notificando con
        anticipación cualquier cancelación; (b) cumplir estrictamente las indicaciones clínicas del profesional
        tratante; (c) realizar los pagos pactados en los plazos acordados. El incumplimiento de estas
        obligaciones exime a LA CLÍNICA de garantías sobre el resultado del tratamiento.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Tercero: Responsabilidad y Garantías</div>
    <div class="clause-body">
        LA CLÍNICA garantiza la calidad del servicio prestado conforme a la <em>lex artis</em> odontológica. No
        obstante, el resultado del tratamiento puede verse afectado por factores biológicos propios del paciente,
        incumplimiento de indicaciones post-operatorias o situaciones de fuerza mayor, por lo que los resultados
        no pueden garantizarse en todos los casos.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Cuarto: Consentimiento Informado</div>
    <div class="clause-body">
        EL PACIENTE declara haber sido informado de forma clara sobre la naturaleza, alcances, riesgos y
        alternativas del tratamiento propuesto, prestando su consentimiento libre, voluntario e informado para
        el inicio del mismo. Asimismo, autoriza a LA CLÍNICA a registrar fotografías clínicas e imágenes
        radiográficas con fines diagnósticos y de seguimiento.
    </div>
</div>

<div class="clause">
    <div class="clause-title">Quinto: Confidencialidad</div>
    <div class="clause-body">
        Toda información clínica y personal de EL PACIENTE será tratada con absoluta confidencialidad,
        conforme a la Ley N.° 29733 – Ley de Protección de Datos Personales, y únicamente podrá ser
        compartida con terceros con autorización expresa del paciente o por mandato legal.
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
