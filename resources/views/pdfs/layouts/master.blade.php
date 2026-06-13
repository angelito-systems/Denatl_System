<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Documento Legal')</title>
    <style>
        /* ══ SISTEMA DE DISEÑO LEGAL v3 · DentalSystem ══ */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Times New Roman", Times, Georgia, serif;
            font-size: 11.5pt;
            color: #334155;
            background: #fff;
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* ── CONTROL DE IMPRESIÓN Y MAQUETACIÓN ── */
        @page {
            size: A4;
            margin: 25mm 25mm 25mm 30mm;
        }
        
        h1, h2, h3, h4, h5, h6, .clause-title, .section-title {
            page-break-after: avoid;
            break-after: avoid;
        }
        p, li, .clause-body {
            orphans: 3;
            widows: 3;
            page-break-inside: auto;
        }
        .clause {
            page-break-inside: auto;
            break-inside: auto;
            margin-bottom: 13px;
        }
        table.prevent-break {
            page-break-inside: auto;
            break-inside: auto;
            width: 100%;
            table-layout: fixed;
        }
        table.prevent-break tr {
            page-break-inside: avoid;
            page-break-after: auto;
            break-inside: avoid;
        }
        table.prevent-break thead {
            display: table-header-group;
        }
        
        .sig-wrap, .firmas, .signatures, .legal-note, .signature-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* ── ENCABEZADO ── */
        .header { display: table; width: 100%; padding-bottom: 12px; margin-bottom: 20px; border-bottom: 2px solid #0d1b2a; }
        .header-logo { display: table-cell; width: 70px; vertical-align: middle; }
        .header-logo img { max-width: 62px; max-height: 62px; }
        .header-logo-placeholder { width: 56px; height: 56px; background: #0d1b2a; border-radius: 4px; font-size: 18px; font-weight: bold; color: #fff; text-align: center; line-height: 56px; font-family: Arial, sans-serif; letter-spacing: -1px; }
        .header-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .header-clinica { font-family: Arial, Helvetica, sans-serif; font-size: 14pt; font-weight: bold; color: #0d1b2a; text-transform: uppercase; letter-spacing: 1px; }
        .header-meta { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #64748b; margin-top: 3px; }
        .header-meta span { margin-right: 14px; }
        .header-doc { display: table-cell; vertical-align: middle; text-align: right; width: 170px; }
        .doc-type-label { font-family: Arial, sans-serif; font-size: 7.5pt; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .doc-badge { display: inline-block; background: #0d1b2a; color: #fff; padding: 4px 13px; border-radius: 2px; font-family: Arial, sans-serif; font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-sub { font-family: Arial, sans-serif; font-size: 8.5pt; color: #94a3b8; margin-top: 5px; }

        /* ── TÍTULO ── */
        .doc-title { text-align: center; margin: 18px 0 16px; padding-bottom: 12px; border-bottom: 1px solid #e8e0d0; }
        .doc-title h1 { font-size: 13pt; font-weight: bold; color: #0d1b2a; text-transform: uppercase; letter-spacing: 1.2px; line-height: 1.35; }
        .doc-title .subtitle { font-size: 9.5pt; color: #64748b; margin-top: 5px; font-style: italic; }

        /* ── PARTES ── */
        .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; border: 1px solid #e8e0d0; }
        .parties-table td { padding: 7px 12px; border: 1px solid #e8e0d0; font-size: 11pt; }
        .parties-table .lbl { background: #f7f4ef; color: #0d1b2a; font-family: Arial, sans-serif; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.4px; width: 145px; white-space: nowrap; }

        /* ── CLÁUSULAS ── */
        .clause-title { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; font-weight: bold; color: #0d1b2a; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; padding: 4px 10px; background: #f7f4ef; border-left: 3px solid #1a3a5c; }
        .clause-body { font-size: 11pt; text-align: justify; color: #334155; padding: 5px 0 5px 14px; line-height: 1.55; }

        /* ── FIRMAS ── */
        .sig-wrap { display: table; width: 100%; margin-top: 50px; }
        .sig-box  { display: table-cell; width: 50%; text-align: center; padding: 0 24px; vertical-align: bottom; }
        .sig-img  { min-height: 65px; display: block; text-align: center; }
        .sig-img img { max-height: 65px; }
        .sig-line { border-top: 1.5px solid #0d1b2a; padding-top: 7px; margin-top: 3px; }
        .sig-name { font-family: Arial, sans-serif; font-size: 10.5pt; font-weight: bold; color: #0d1b2a; }
        .sig-role { font-family: Arial, sans-serif; font-size: 8.5pt; color: #94a3b8; margin-top: 2px; }

        /* ── NOTA LEGAL ── */
        .legal-note { margin-top: 22px; padding: 8px 12px; background: #f7f4ef; border: 1px solid #e8e0d0; border-radius: 2px; font-family: Arial, sans-serif; font-size: 8pt; color: #94a3b8; text-align: center; line-height: 1.6; }

        /* ── PIE (HACK PARA NO SOLAPAR) ── */
        .footer-fixed { position: fixed; bottom: 0; left: 0; right: 0; height: 35px; border-top: 1px solid #e8e0d0; display: table; width: 100%; padding-top: 5px; background: #fff; z-index: 10; }
        .footer-spacer { height: 40px; }
        .footer-left  { display: table-cell; font-family: Arial, sans-serif; font-size: 7.5pt; color: #94a3b8; vertical-align: middle; }
        .footer-right { display: table-cell; font-family: Arial, sans-serif; font-size: 7.5pt; color: #94a3b8; text-align: right; vertical-align: middle; }
        
        @yield('css')
    </style>
</head>
<body>

<!-- FIXED FOOTER -->
<div class="footer-fixed">
    @hasSection('footer')
        @yield('footer')
    @else
        <div class="footer-left">Generado el {{ $fecha ?? date('d/m/Y') }} &nbsp;·&nbsp; {{ $clinica['nombre'] ?? 'Clínica Dental' }}</div>
        <div class="footer-right">Documento Legal &nbsp;·&nbsp; Conservar en archivo del paciente &nbsp;·&nbsp; Validez contractual conforme a los términos de la clínica</div>
    @endif
</div>

<!-- CONTENT WRAPPER -->
<table style="width: 100%; border: none; border-collapse: collapse; margin: 0; padding: 0;">
    <thead>
        <tr><td style="border: none; padding: 0;">
            @yield('header')
        </td></tr>
    </thead>
    <tbody>
        <tr><td style="border: none; padding: 0;">
            @yield('content')
        </td></tr>
    </tbody>
    <tfoot>
        <tr><td style="border: none; padding: 0;">
            <div class="footer-spacer"></div>
        </td></tr>
    </tfoot>
</table>

</body>
</html>
