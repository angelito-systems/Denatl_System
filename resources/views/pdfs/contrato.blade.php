<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Servicios – {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
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

        .doc-title { text-align: center; margin: 20px 0 18px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
        .doc-title h1 { font-size: 14px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 1.5px; }
        .doc-title .subtitle { font-size: 10px; color: #64748b; margin-top: 4px; font-style: italic; }

        .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .parties-table td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 11px; }
        .parties-table .lbl { background: #f1f5f9; color: #475569; font-weight: bold; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.4px; width: 130px; white-space: nowrap; }

        .clause { margin-bottom: 14px; }
        .clause-title { font-size: 10.5px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; padding: 5px 10px; background: #f1f5f9; border-left: 3px solid #2563eb; }
        .clause-body { font-size: 11px; text-align: justify; color: #334155; padding: 4px 0 4px 13px; line-height: 1.7; }

        .sig-wrap { display: table; width: 100%; margin-top: 52px; }
        .sig-box  { display: table-cell; width: 50%; text-align: center; padding: 0 26px; vertical-align: bottom; }
        .sig-img  { min-height: 62px; display: flex; align-items: flex-end; justify-content: center; }
        .sig-img img { max-height: 70px; }
        .sig-line { border-top: 1.5px solid #0f2040; padding-top: 7px; margin-top: 2px; }
        .sig-name { font-size: 11px; font-weight: bold; color: #0f2040; }
        .sig-role { font-size: 9.5px; color: #94a3b8; margin-top: 1px; }

        .legal-note { margin-top: 24px; padding: 9px 12px; background: #f8faff; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 9px; color: #94a3b8; text-align: center; line-height: 1.6; }

        .footer { position: fixed; bottom: -17px; left: 0; right: 0; border-top: 1.5px solid #e2e8f0; display: table; width: 100%; padding-top: 6px; background: #fff; }
        .footer-left  { display: table-cell; font-size: 8.5px; color: #94a3b8; vertical-align: middle; }
        .footer-right { display: table-cell; font-size: 8.5px; color: #94a3b8; text-align: right; vertical-align: middle; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">Generado el {{ $fecha }} · {{ $clinica['nombre'] }}</div>
    <div class="footer-right">Documento Legal · Conservar en archivo del paciente · Este documento tiene validez contractual según los términos de la clínica</div>
</div>

{{-- HEADER --}}
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
        LA CLÍNICA garantiza la calidad del servicio prestado conforme a la lex artis odontológica. No obstante,
        el resultado del tratamiento puede verse afectado por factores biológicos propios del paciente,
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
        conforme a la Ley N° 29733 – Ley de Protección de Datos Personales, y únicamente podrá ser
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
    La información del paciente es confidencial conforme a la Ley N° 29733 – Ley de Protección de Datos Personales.
</div>

</body>
</html>
