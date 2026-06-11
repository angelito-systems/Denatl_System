<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Servicio - {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #2563EB; padding-bottom: 10px; margin-bottom: 20px; }
        .clinica-nombre { font-size: 22px; font-weight: bold; color: #2563EB; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; text-decoration: underline; }
        .content { margin-top: 20px; text-align: justify; }
        .firmas { margin-top: 80px; width: 100%; display: table; }
        .firma { display: table-cell; width: 50%; text-align: center; }
        .linea { border-top: 1px solid #000; width: 80%; margin: 0 auto; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="header">
    <div class="clinica-nombre">{{ $clinica['nombre'] }}</div>
</div>

<div class="title">CONTRATO DE PRESTACIÓN DE SERVICIOS ODONTOLÓGICOS</div>

<div class="content">
    <p>Conste por el presente documento, el acuerdo de prestación de servicios odontológicos que celebran, de una parte, <strong>{{ $clinica['nombre'] }}</strong> (en adelante "LA CLÍNICA"), y de la otra parte, el/la Sr(a) <strong>{{ $paciente->first_name }} {{ $paciente->last_name }}</strong>, identificado con DNI N° <strong>{{ $paciente->dni ?? '___' }}</strong> (en adelante "EL PACIENTE").</p>
    
    <p><strong>PRIMERO: OBJETO DEL CONTRATO</strong><br>
    LA CLÍNICA se compromete a prestar servicios odontológicos especializados a favor de EL PACIENTE de acuerdo a la historia clínica y plan de tratamiento acordado verbalmente o por escrito.</p>

    <p><strong>SEGUNDO: OBLIGACIONES DE EL PACIENTE</strong><br>
    EL PACIENTE se compromete a asistir puntualmente a sus citas, cumplir estrictamente con las indicaciones del profesional tratante, y realizar los pagos correspondientes según el avance o acuerdo previo. El incumplimiento exime a LA CLÍNICA de garantías futuras sobre el tratamiento.</p>

    <p><strong>TERCERO: CONSENTIMIENTO INFORMADO</strong><br>
    EL PACIENTE declara haber sido informado satisfactoriamente sobre la naturaleza de los tratamientos a realizar, los posibles riesgos, beneficios y alternativas, prestando su consentimiento libre y voluntario para el inicio de los mismos.</p>

    <p>En señal de conformidad, ambas partes firman este documento el {{ $fecha }}.</p>
</div>

<div class="firmas">
    <div class="firma">
        <div class="linea"></div>
        LA CLÍNICA<br>
        {{ $clinica['nombre'] }}
    </div>
    <div class="firma">
        <div class="linea"></div>
        EL PACIENTE<br>
        {{ $paciente->first_name }} {{ $paciente->last_name }}<br>
        DNI: {{ $paciente->dni ?? '___' }}
    </div>
</div>

</body>
</html>
