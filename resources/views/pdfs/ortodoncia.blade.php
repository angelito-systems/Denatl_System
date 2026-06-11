<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Ortodoncia</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #0ea5e9; text-transform: uppercase; margin: 0; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        .content { margin-top: 20px; text-align: justify; }
        .signatures { margin-top: 60px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; }
        .signature-line { border-top: 1px solid #333; width: 80%; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $clinica['nombre'] ?? 'Clínica Dental' }}</h1>
        <p class="subtitle">Contrato de Tratamiento de Ortodoncia</p>
    </div>

    <div class="content">
        <p>Por el presente documento, el paciente <strong>{{ $paciente->first_name }} {{ $paciente->last_name }}</strong>, identificado con DNI <strong>{{ $paciente->dni }}</strong>, acepta los términos y condiciones del tratamiento de ortodoncia propuesto en esta clínica.</p>
        
        <p><em>[Aquí el administrador de la clínica puede agregar las cláusulas específicas del contrato, riesgos, costos y compromisos del tratamiento de ortodoncia.]</em></p>

        <p>Firmado el día <strong>{{ $fecha }}</strong>.</p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Firma del Paciente</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Representante de la Clínica</div>
        </div>
    </div>
</body>
</html>
