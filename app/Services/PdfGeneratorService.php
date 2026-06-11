<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Configuration;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfGeneratorService
{
    private array $clinica;

    public function __construct()
    {
        $this->clinica = [
            'nombre' => Configuration::get('clinica_nombre', 'Clínica Dental System'),
            'ruc' => Configuration::get('clinica_ruc', ''),
            'telefono' => Configuration::get('clinica_telefono', ''),
            'direccion' => Configuration::get('clinica_direccion', ''),
        ];
    }

    private function applyBrowsershotConfig($pdf)
    {
        return $pdf->withBrowsershot(function ($browsershot) {
            if ($nodePath = env('BROWSERSHOT_NODE_BIN')) {
                $browsershot->setNodeBinary($nodePath);
            }
            if ($npmPath = env('BROWSERSHOT_NPM_BIN')) {
                $browsershot->setNpmBinary($npmPath);
            }
            if ($nodeModulesPath = env('BROWSERSHOT_NODE_MODULES_PATH', base_path('node_modules'))) {
                $browsershot->setNodeModulePath($nodeModulesPath);
            }
            if ($chromePath = env('BROWSERSHOT_CHROME_PATH')) {
                $browsershot->setChromePath($chromePath);
            }
        });
    }

    /**
     * Genera el PDF de la Historia Clínica completa de un paciente.
     */
    public function generarHistoriaClinica(Patient $paciente)
    {
        $pdf = Pdf::view('pdfs.historia-clinica', [
            'paciente' => $paciente->load(['payments']),
            'clinica' => $this->clinica,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->name("historia_{$paciente->first_name}_{$paciente->last_name}.pdf");
    }

    /**
     * Genera el PDF de una plantilla para un paciente.
     */
    public function generarPlantilla(Patient $paciente, string $tipoPlantilla, ?string $signature = null)
    {
        $vistas = [
            'ortodoncia' => 'pdfs.contrato-ortodoncia',
            'implantes' => 'pdfs.contrato-implantes',
            'consentimiento' => 'pdfs.consentimiento',
            'contrato' => 'pdfs.contrato',
        ];

        $vista = $vistas[$tipoPlantilla] ?? 'pdfs.contrato';

        $pdf = Pdf::view($vista, [
            'paciente' => $paciente,
            'clinica' => $this->clinica,
            'fecha' => now()->format('d/m/Y'),
            'signature' => $signature
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->name("{$tipoPlantilla}_{$paciente->first_name}_{$paciente->last_name}.pdf");
    }

    /**
     * Genera el PDF de un comprobante de pago.
     */
    public function generarComprobante($pago)
    {
        $pdf = Pdf::view('pdfs.comprobante', [
            'pago' => $pago,
            'clinica' => $this->clinica,
        ]);

        return $this->applyBrowsershotConfig($pdf)
            ->format('a5')
            ->margins(10, 10, 10, 10)
            ->name("comprobante_{$pago->id}.pdf");
    }
}
