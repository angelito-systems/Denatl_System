<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento Informado – {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
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

        .info-box { background: #f8faff; border: 1px solid #dbeafe; border-left: 4px solid #2563eb; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; display: table; width: 100%; }
        .info-box-col { display: table-cell; vertical-align: top; padding-right: 20px; }
        .info-box-col + .info-box-col { padding-left: 20px; padding-right: 0; border-left: 1px solid #dbeafe; }
        .ib-label { font-size: 9px; color: #2563eb; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .ib-name { font-size: 13px; font-weight: bold; color: #0f2040; }
        .ib-meta { font-size: 10px; color: #475569; margin-top: 2px; }

        .section-title { font-size: 10.5px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 0.5px; margin: 16px 0 6px; padding: 5px 10px; background: #f1f5f9; border-left: 3px solid #2563eb; }
        .body-text { font-size: 11px; text-align: justify; color: #334155; padding: 4px 0 4px 13px; line-height: 1.7; }

        .check-list { margin: 6px 0 10px 13px; padding: 0; list-style: none; }
        .check-list li { font-size: 11px; color: #334155; margin-bottom: 5px; padding-left: 20px; position: relative; line-height: 1.55; }
        .check-list li::before { content: "▸"; position: absolute; left: 0; color: #2563eb; font-size: 12px; }

        .declaration { background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #c9a227; border-radius: 4px; padding: 11px 14px; margin: 18px 0; font-size: 11px; color: #1e293b; text-align: justify; line-height: 1.7; }

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
    <div class="footer-right">Documento Legal · Conservar en Historia Clínica · Este documento tiene validez contractual según los términos de la clínica</div>
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
        <div class="ib-meta" style="margin-top: 4px;">
            @if($paciente->phone) Tel: {{ $paciente->phone }}<br>@endif
            @if($paciente->email) {{ $paciente->email }}@endif
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

<div class="section-title">He sido informado sobre los siguientes aspectos</div>
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
            <div class="sig-role">Paciente · DNI: {{ $paciente->dni ?? '___________' }}</div>
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
    Este documento tiene validez informativa y contractual según los términos de la clínica. La información del paciente es confidencial conforme a la Ley N° 29733.
</div>

</body>
</html>
