@extends('pdfs.layouts.master')
@section('title', 'Odontograma – {{ $paciente->first_name }} {{ $paciente->last_name }}')

@section('content')
<div class="header">
    <div class="header-logo">
        @if(!empty($clinica['logo_base64']))
            <img src="{{ $clinica['logo_base64'] }}" alt="Logo">
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
@endsection
