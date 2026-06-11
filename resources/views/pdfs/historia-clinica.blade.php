<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia Clínica - {{ $paciente->first_name }} {{ $paciente->last_name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #2563EB; padding-bottom: 20px; margin-bottom: 20px; }
        .clinica-nombre { font-size: 24px; font-weight: bold; color: #2563EB; }
        .clinica-datos { font-size: 12px; color: #666; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        .section-title { background-color: #f3f4f6; padding: 8px; font-weight: bold; margin-top: 20px; border-left: 4px solid #2563EB; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; font-size: 12px; }
        td { font-size: 12px; }
        .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 30px; font-size: 10px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

<div class="header">
    <div class="clinica-nombre">{{ $clinica['nombre'] }}</div>
    <div class="clinica-datos">
        @if($clinica['ruc']) RUC: {{ $clinica['ruc'] }} | @endif
        @if($clinica['telefono']) Tel: {{ $clinica['telefono'] }} | @endif
        {{ $clinica['direccion'] }}
    </div>
</div>

<div class="title">Historia Clínica Odontológica</div>

<div class="section-title">Datos del Paciente</div>
<table>
    <tr>
        <th>Nombres y Apellidos</th>
        <td colspan="3">{{ $paciente->first_name }} {{ $paciente->last_name }}</td>
    </tr>
    <tr>
        <th>DNI / Documento</th>
        <td>{{ $paciente->dni ?? '---' }}</td>
        <th>Género</th>
        <td>{{ $paciente->gender ?? '---' }}</td>
    </tr>
    <tr>
        <th>Teléfono</th>
        <td>{{ $paciente->phone ?? '---' }}</td>
        <th>Fecha de Nacimiento</th>
        <td>{{ $paciente->date_of_birth ? \Carbon\Carbon::parse($paciente->date_of_birth)->format('d/m/Y') : '---' }}</td>
    </tr>
</table>

<div class="section-title">Tratamientos Realizados</div>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tratamiento</th>
            <th>Pieza</th>
            <th>Costo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paciente->treatments as $tratamiento)
            <tr>
                <td>{{ $tratamiento->created_at->format('d/m/Y') }}</td>
                <td>{{ $tratamiento->name }}</td>
                <td>{{ $tratamiento->tooth_number ?? 'General' }}</td>
                <td>S/ {{ number_format($tratamiento->cost, 2) }}</td>
                <td>{{ $tratamiento->status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center;">No hay tratamientos registrados.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Documento generado el {{ $fecha }} por Dental System.
</div>

</body>
</html>
