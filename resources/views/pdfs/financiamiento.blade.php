<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Financiamiento – {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
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
        .header-doc { display: table-cell; vertical-align: middle; text-align: right; width: 185px; }
        .doc-type-label { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .doc-badge { display: inline-block; background: #059669; color: #fff; padding: 5px 14px; border-radius: 4px; font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-sub { font-size: 9.5px; color: #94a3b8; margin-top: 5px; }

        .doc-title { text-align: center; margin: 20px 0 18px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
        .doc-title h1 { font-size: 14px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 1.5px; }
        .doc-title .subtitle { font-size: 10px; color: #64748b; margin-top: 4px; font-style: italic; }

        .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; border: 1px solid #e2e8f0; }
        .parties-table td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 11px; }
        .parties-table .lbl { background: #f1f5f9; color: #475569; font-weight: bold; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.4px; width: 130px; white-space: nowrap; }

        /* FINANCIAMIENTO SUMMARY */
        .fin-summary { display: table; width: 100%; margin-bottom: 20px; }
        .fin-cell { display: table-cell; width: 33.33%; padding: 0 5px; vertical-align: top; }
        .fin-cell:first-child { padding-left: 0; }
        .fin-cell:last-child  { padding-right: 0; }
        .fin-card { border: 1px solid #e2e8f0; border-top: 3px solid #059669; border-radius: 4px; padding: 12px 14px; background: #f0fdf4; text-align: center; }
        .fin-card.blue { border-top-color: #2563eb; background: #f8faff; }
        .fin-card.gold { border-top-color: #c9a227; background: #fffbeb; }
        .fin-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: bold; }
        .fin-value { font-size: 18px; font-weight: bold; color: #0f2040; margin: 5px 0 1px; }
        .fin-value.green { color: #059669; }
        .fin-value.blue  { color: #2563eb; }
        .fin-value.gold  { color: #c9a227; }
        .fin-sub { font-size: 9px; color: #94a3b8; }

        .clause { margin-bottom: 14px; }
        .clause-title { font-size: 10.5px; font-weight: bold; color: #0f2040; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; padding: 5px 10px; background: #f1f5f9; border-left: 3px solid #059669; }
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

@php
    $contrato = $paciente->treatmentContracts()->latest()->first();
@endphp

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
        anticipación cualquier cancelación y a cumplir estrictamente las indicaciones clínicas del profesional.
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
    La información del paciente es confidencial conforme a la Ley N° 29733 – Ley de Protección de Datos Personales.
</div>

</body>
</html>
