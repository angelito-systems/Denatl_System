<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfGeneratorService
{
    private array $clinica;

    public function __construct()
    {
        $logoPath = Configuration::get('logo_path');
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath);
            $content = base64_encode(Storage::disk('public')->get($logoPath));
            $logoBase64 = "data:{$mime};base64,{$content}";
        }

        $this->clinica = [
            'nombre' => Configuration::get('clinica_nombre', 'Clínica Dental System'),
            'ruc' => Configuration::get('clinica_ruc', ''),
            'telefono' => Configuration::get('clinica_telefono', ''),
            'direccion' => Configuration::get('clinica_direccion', ''),
            'logo_base64' => $logoBase64,
            'color_primary' => Configuration::get('pdf_color_primary', '#0d1b2a'),
            'color_secondary' => Configuration::get('pdf_color_secondary', '#64748b'),
            'representante' => Configuration::get('representante_legal', ''),
            'representante_dni' => Configuration::get('representante_dni', ''),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Configuración de Browsershot (Node / Chrome)
    // ─────────────────────────────────────────────────────────────────────────

    public function applyBrowsershotConfig($pdf)
    {
        return $pdf->withBrowsershot(function ($browsershot) {
            if ($nodePath = env('BROWSERSHOT_NODE_BIN')) {
                $browsershot->setNodeBinary($nodePath);
            }
            if ($npmPath = env('BROWSERSHOT_NPM_BIN')) {
                $browsershot->setNpmBinary($npmPath);
            }
            $nodeModulesPath = env('BROWSERSHOT_NODE_MODULES_PATH', base_path('node_modules'));
            if ($nodeModulesPath) {
                $browsershot->setNodeModulePath($nodeModulesPath);
            }
            if ($chromePath = env('BROWSERSHOT_CHROME_PATH')) {
                $browsershot->setChromePath($chromePath);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Historia Clínica
    // ─────────────────────────────────────────────────────────────────────────

    public function generarHistoriaClinica(Patient $paciente)
    {
        $pdf = Pdf::view('pdfs.historia-clinica', [
            'paciente' => $paciente->load(['payments']),
            'clinica' => $this->clinica,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->margins(25, 25, 25, 30)
            ->name("historia_{$paciente->first_name}_{$paciente->last_name}.pdf");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Odontograma
    // ─────────────────────────────────────────────────────────────────────────

    public function generarOdontograma(Patient $paciente, string $htmlContent)
    {
        $pdf = Pdf::view('pdfs.odontograma', [
            'paciente' => $paciente,
            'clinica' => $this->clinica,
            'fecha' => now()->format('d/m/Y H:i'),
            'htmlContent' => $htmlContent,
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->landscape() // Landscape works better for wide grids like Odontograms
            ->margins(25, 25, 25, 30)
            ->name("odontograma_{$paciente->first_name}_{$paciente->last_name}.pdf");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Plantillas (consentimiento, contrato, etc.)
    // ─────────────────────────────────────────────────────────────────────────

    public function generarPlantilla(
        Patient $paciente,
        string $tipoPlantilla,
        ?string $signature = null,
        ?string $adminSignature = null
    ) {
        $vistas = [
            'ortodoncia' => 'pdfs.ortodoncia',
            'implantes' => 'pdfs.implantes',
            'consentimiento' => 'pdfs.consentimiento',
            'contrato' => 'pdfs.contrato',
            'financiamiento' => 'pdfs.financiamiento',
        ];

        $vista = $vistas[$tipoPlantilla] ?? 'pdfs.contrato';

        $pdf = Pdf::view($vista, [
            'paciente' => $paciente,
            'clinica' => $this->clinica,
            'fecha' => now()->format('d/m/Y'),
            'signature' => $signature,
            'adminSignature' => $adminSignature,
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->margins(25, 25, 25, 30)
            ->name("{$tipoPlantilla}_{$paciente->first_name}_{$paciente->last_name}.pdf");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Comprobante de pago (Boleta / Factura / Nota de Crédito / Ticket interno)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera el PDF del comprobante a partir del HTML producido por SunatService.
     *
     * @param  Payment  $pago
     * @param  string  $format  'ticket' (80 mm térmica) | 'a4'
     * @param  bool  $isNota  true para Notas de Crédito/Débito
     */
    public function generarComprobante($pago, string $format = 'ticket', bool $isNota = false)
    {
        /** @var SunatService $sunat */
        $sunat = app(SunatService::class);

        // getHtmlReport construye el QR, extrae el hash y renderiza la plantilla
        $html = $sunat->getHtmlReport($pago, $format, $isNota);

        $pdf = Pdf::html($html);

        if ($format === 'ticket') {
            // 80 mm ancho — estándar impresoras térmicas POS
            // Alto 297 mm: la impresora recorta según el contenido real
            $pdf = $this->applyBrowsershotConfig($pdf)
                ->margins(2, 2, 2, 2)
                ->paperSize(80, 297, 'mm');
        } else {
            // A4 estándar para facturas y notas de crédito
            $pdf = $this->applyBrowsershotConfig($pdf)
                ->format('a4')
                ->margins(25, 25, 25, 30);
        }

        return $pdf->name("comprobante_{$pago->id}.pdf");
    }
}
