<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Odontograma – {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
    <!-- TailwindCSS for rendering the SVG classes properly -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 18mm 15mm 22mm 15mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.5;
        }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header-logo {
            display: table-cell;
            width: 70px;
            vertical-align: middle;
        }
        .header-logo img {
            max-width: 65px;
            max-height: 65px;
        }
        .header-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 10px;
        }
        .header-clinica {
            font-size: 18px;
            font-weight: bold;
            color: #1d4ed8;
            text-transform: uppercase;
        }
        .header-doc {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 160px;
        }
        .doc-badge {
            background: #1d4ed8;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        /* DATA GRID */
        .data-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-grid th {
            background: #f0f5ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #d1ddf7;
            text-align: left;
            width: 130px;
        }
        .data-grid td {
            padding: 6px 8px;
            border: 1px solid #e5eaf5;
            font-size: 11px;
        }

        /* ODONTOGRAMA CONTAINER */
        .odontograma-wrapper {
            margin-top: 20px;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        /* FOOTER */
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0; right: 0;
            border-top: 1px solid #d1ddf7;
            padding-top: 6px;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            font-size: 9px;
            color: #aaa;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">Generado el {{ $fecha }} · {{ $clinica['nombre'] }}</div>
</div>

<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo']))
            <img src="data:image/png;base64,{{ $clinica['logo'] }}" alt="Logo">
        @endif
    </div>
    <div class="header-info">
        <div class="header-clinica">{{ $clinica['nombre'] }}</div>
    </div>
    <div class="header-doc">
        <div class="doc-badge">Odontograma Clínico</div>
    </div>
</div>

<table class="data-grid">
    <tr>
        <th>Paciente</th>
        <td colspan="3"><strong>{{ strtoupper($paciente->last_name) }}, {{ $paciente->first_name }}</strong></td>
    </tr>
    <tr>
        <th>DNI</th>
        <td>{{ $paciente->dni ?? '—' }}</td>
        <th>Fecha de Nacimiento</th>
        <td>{{ $paciente->date_of_birth ? \Carbon\Carbon::parse($paciente->date_of_birth)->format('d/m/Y') : '—' }}</td>
    </tr>
</table>

<div class="odontograma-wrapper">
    <!-- Renderizado del HTML enviado por el cliente (SVG y Grid de Svelte) -->
    {!! $htmlContent !!}
</div>

</body>
</html>
