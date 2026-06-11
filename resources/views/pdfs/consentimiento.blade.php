<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento Informado</title>
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
        <p class="subtitle">Consentimiento Informado General</p>
    </div>

    <div class="content">
        <p>Yo, <strong>{{ $paciente->first_name }} {{ $paciente->last_name }}</strong>, identificado con DNI <strong>{{ $paciente->dni }}</strong>, declaro haber sido informado de forma clara y comprensible sobre mi diagnóstico y el tratamiento propuesto.</p>
        
        <p><em>[Agregar cláusulas estándar sobre anestesia local, posibles complicaciones menores, y autorización para tomar radiografías y fotografías clínicas.]</em></p>

        <p>En fe de lo cual, firmo este documento libre y voluntariamente el día <strong>{{ $fecha }}</strong>.</p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Firma del Paciente</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Odontólogo Tratante</div>
        </div>
    </div>
</body>
</html>
