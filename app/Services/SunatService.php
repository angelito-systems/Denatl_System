<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Payment;
use App\Models\TreatmentContract;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\HtmlReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

class SunatService
{
    private See $see;

    private Company $company;

    public function __construct()
    {
        $this->see = new See;

        $environment = Configuration::get('sunat_environment') ?: 'demo';
        $certPem = Configuration::get('sunat_cert_pem') ?: '';
        $ruc = Configuration::get('sunat_ruc') ?: '20000000000';
        $user = Configuration::get('sunat_sol_user') ?: 'MODDATOS';
        $pass = Configuration::get('sunat_sol_pass') ?: 'moddatos';
        $razonSocial = Configuration::get('sunat_razon_social') ?: 'MI CLINICA DENTAL SAC';

        // ── Certificado y endpoint ────────────────────────────────────────────
        if ($environment === 'demo' || ! $certPem) {
            if (! $certPem) {
                $certPath = storage_path('app/private/certificate.pem');
                if (file_exists($certPath)) {
                    $certPem = file_get_contents($certPath) ?: '';
                } else {
                    throw new Exception(
                        "Certificado no encontrado en: {$certPath}. ".
                            "Coloca 'certificate.pem' en storage/app/private/ o súbelo desde Configuración."
                    );
                }
            }
            $this->see->setService(SunatEndpoints::FE_BETA);
        } else {
            $this->see->setService(SunatEndpoints::FE_PRODUCCION);
        }

        if (! empty($certPem)) {
            $this->see->setCertificate($certPem);
        }
        $this->see->setClaveSOL($ruc, $user, $pass);

        // ── Empresa emisora ───────────────────────────────────────────────────
        $address = (new Address)
            ->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('LIMA')
            ->setDireccion(Configuration::get('clinica_direccion') ?: 'Av. Principal 123');

        $this->company = (new Company)
            ->setRuc($ruc)
            ->setRazonSocial($razonSocial)
            ->setNombreComercial(Configuration::get('clinica_nombre') ?: 'Clínica Dental')
            ->setAddress($address);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción de comprobantes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye un Invoice (Factura o Boleta) a partir del Payment.
     */
    public function buildInvoice(Payment $payment): Invoice
    {
        $isFactura = $payment->receipt_type === 'Factura';
        $tipoDoc = $isFactura ? '01' : '03';
        $serie = $payment->sunat_serie ?: ($isFactura ? 'F001' : 'B001');

        if ($payment->sunat_correlativo) {
            $correlativo = $payment->sunat_correlativo;
        } else {
            $correlativo = $this->getNextCorrelativo($serie);
        }

        $client = $this->buildClient($payment, $isFactura);

        [$baseIgv, $igv, $total] = $this->calcularMontos($payment->amount);

        $detail = (new SaleDetail)
            ->setCodProducto('S001')
            ->setUnidad('ZZ')                               // Servicios Cat. 03
            ->setDescripcion($payment->notes ?: 'Tratamiento Odontológico')
            ->setCantidad(1)
            ->setMtoValorUnitario($baseIgv)
            ->setMtoValorVenta($baseIgv)
            ->setMtoBaseIgv($baseIgv)
            ->setPorcentajeIgv(18.00)
            ->setIgv($igv)
            ->setTipAfeIgv('10')                            // Gravado – Onerosa
            ->setTotalImpuestos($igv)
            ->setMtoPrecioUnitario($total);

        return (new Invoice)
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc($tipoDoc)
            ->setSerie($serie)
            ->setCorrelativo($correlativo)
            ->setFechaEmision(new \DateTime)
            ->setTipoMoneda('PEN')
            ->setCompany($this->company)
            ->setClient($client)
            ->setMtoOperGravadas($baseIgv)
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setValorVenta($baseIgv)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails([$detail])
            ->setLegends([
                (new Legend)
                    ->setCode('1000')
                    ->setValue($this->numeroALetras($total)),
            ]);
    }

    /**
     * Construye una Nota de Crédito (tipoDoc '07') vinculada a un comprobante previo.
     *
     * @param  Payment  $payment  Pago de la nota de crédito
     * @param  string  $serieRef  Serie del comprobante de referencia (ej. 'F001')
     * @param  string  $correlativoRef  Correlativo de referencia (ej. '00000001')
     * @param  string  $tipoDocRef  Tipo doc referencia: '01' Factura | '03' Boleta
     * @param  string  $codMotivo  Motivo: '01' Anulación, '02' Anulación errores, etc.
     * @param  string  $descMotivo  Descripción libre del motivo
     */
    public function buildNotaCredito(
        Payment $payment,
        string $serieRef,
        string $correlativoRef,
        string $tipoDocRef = '01',
        string $codMotivo = '01',
        string $descMotivo = 'ANULACIÓN DE OPERACIÓN'
    ): Note {
        $isFactura = str_starts_with($serieRef, 'F');
        $serie = $payment->sunat_serie ?: ($isFactura ? 'FC01' : 'BC01');

        if ($payment->sunat_correlativo) {
            $correlativo = $payment->sunat_correlativo;
        } else {
            $correlativo = $this->getNextCorrelativo($serie);
        }

        $client = $this->buildClient($payment, $isFactura);

        [$baseIgv, $igv, $total] = $this->calcularMontos($payment->amount);

        $detail = (new SaleDetail)
            ->setCodProducto('S001')
            ->setUnidad('ZZ')
            ->setDescripcion($payment->notes ?: 'Nota de Crédito – Tratamiento Odontológico')
            ->setCantidad(1)
            ->setMtoValorUnitario($baseIgv)
            ->setMtoValorVenta($baseIgv)
            ->setMtoBaseIgv($baseIgv)
            ->setPorcentajeIgv(18.00)
            ->setIgv($igv)
            ->setTipAfeIgv('10')
            ->setTotalImpuestos($igv)
            ->setMtoPrecioUnitario($total);

        return (new Note)
            ->setUblVersion('2.1')
            ->setTipoDoc('07')                              // 07 = Nota de Crédito
            ->setSerie($serie)
            ->setCorrelativo($correlativo)
            ->setFechaEmision(new \DateTime)
            ->setTipoMoneda('PEN')
            ->setCompany($this->company)
            ->setClient($client)
            ->setCodMotivo($codMotivo)
            ->setDesMotivo($descMotivo)
            ->setTipDocAfectado($tipoDocRef)
            ->setNumDocfectado("{$serieRef}-{$correlativoRef}")
            ->setMtoOperGravadas($baseIgv)
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setValorVenta($baseIgv)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails([$detail])
            ->setLegends([
                (new Legend)
                    ->setCode('1000')
                    ->setValue($this->numeroALetras($total)),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Emisión a SUNAT
    // ─────────────────────────────────────────────────────────────────────────

    public function emitirComprobante(Payment $payment): Payment
    {
        if (! in_array($payment->receipt_type, ['Boleta', 'Factura'])) {
            throw new Exception('Solo se pueden emitir Boletas y Facturas a SUNAT.');
        }

        $invoice = $this->buildInvoice($payment);
        $serie = $invoice->getSerie();
        $correlativo = $invoice->getCorrelativo();

        $result = $this->see->send($invoice);

        $payment->sunat_serie = $serie;
        $payment->sunat_correlativo = $correlativo;

        // ── XML firmado ───────────────────────────────────────────────────────
        $xmlSigned = $this->see->getFactory()->getLastXml();
        $xmlName = $invoice->getName().'.xml';
        Storage::disk('public')->put('sunat/'.$xmlName, $xmlSigned);
        $payment->sunat_xml_path = 'sunat/'.$xmlName;
        $payment->sunat_hash = $this->extractHashFromXml($xmlSigned);

        if (! $result->isSuccess()) {
            $payment->sunat_status = 'Rechazado';
            $payment->sunat_message = $result->getError()->getCode().' - '.$result->getError()->getMessage();
            $payment->save();
            throw new Exception($payment->sunat_message);
        }

        $cdr = $result->getCdrResponse();
        $payment->sunat_status = $cdr->isAccepted() ? 'Aceptado' : 'Rechazado';
        $payment->sunat_message = $cdr->getDescription();

        // ── CDR ───────────────────────────────────────────────────────────────
        $cdrName = 'R-'.$invoice->getName().'.zip';
        Storage::disk('public')->put('sunat/'.$cdrName, $result->getCdrZip());
        $payment->sunat_cdr_path = 'sunat/'.$cdrName;

        $payment->save();

        return $payment;
    }

    /**
     * Emite una Nota de Crédito a SUNAT.
     */
    public function emitirNotaCredito(
        Payment $payment,
        string $serieRef,
        string $correlativoRef,
        string $tipoDocRef = '01',
        string $codMotivo = '01',
        string $descMotivo = 'ANULACIÓN DE OPERACIÓN'
    ): Payment {
        $note = $this->buildNotaCredito($payment, $serieRef, $correlativoRef, $tipoDocRef, $codMotivo, $descMotivo);
        $result = $this->see->send($note);

        $payment->sunat_serie = $note->getSerie();
        $payment->sunat_correlativo = $note->getCorrelativo();

        $xmlSigned = $this->see->getFactory()->getLastXml();
        $xmlName = $note->getName().'.xml';
        Storage::disk('public')->put('sunat/'.$xmlName, $xmlSigned);
        $payment->sunat_xml_path = 'sunat/'.$xmlName;
        $payment->sunat_hash = $this->extractHashFromXml($xmlSigned);

        if (! $result->isSuccess()) {
            $payment->sunat_status = 'Rechazado';
            $payment->sunat_message = $result->getError()->getCode().' - '.$result->getError()->getMessage();
            $payment->save();
            throw new Exception($payment->sunat_message);
        }

        $cdr = $result->getCdrResponse();
        $payment->sunat_status = $cdr->isAccepted() ? 'Aceptado' : 'Rechazado';
        $payment->sunat_message = $cdr->getDescription();

        $cdrName = 'R-'.$note->getName().'.zip';
        Storage::disk('public')->put('sunat/'.$cdrName, $result->getCdrZip());
        $payment->sunat_cdr_path = 'sunat/'.$cdrName;

        $payment->save();

        return $payment;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generación de HTML para PDF
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera el HTML del comprobante.
     *
     * @param  string  $format  'ticket' (80 mm) | 'a4'
     * @param  bool  $isNota  true para Notas de Crédito/Débito
     */
    public function getHtmlReport(Payment $payment, string $format = 'a4', bool $isNota = false): string
    {
        $doc = $isNota
            ? $this->buildNotaCredito($payment, $payment->sunat_serie_ref ?? 'F001', $payment->sunat_correlativo_ref ?? '00000001')
            : $this->buildInvoice($payment);

        // ── Hash ─────────────────────────────────────────────────────────────
        $hash = '';
        if ($payment->sunat_hash) {
            $hash = $payment->sunat_hash;
        } elseif ($payment->sunat_xml_path) {
            $xmlContent = Storage::disk('public')->get($payment->sunat_xml_path);
            if ($xmlContent) {
                $hash = $this->extractHashFromXml($xmlContent);
            }
        } else {
            try {
                $xmlSigned = $this->see->getXmlSigned($doc);
                $hash = $this->extractHashFromXml($xmlSigned);
            } catch (\Throwable) {
                $hash = '';
            }
        }

        $extras = [];
        if ($payment->treatment_contract_id) {
            $contract = TreatmentContract::find($payment->treatment_contract_id);
            if ($contract) {
                $extras[] = ['name' => 'Saldo Pendiente', 'value' => 'S/ '.number_format($contract->balance_due, 2)];
                $extras[] = ['name' => 'Tratamiento', 'value' => $contract->treatment_name];
            }
        }

        if ($format === 'ticket') {
            // ── Ticket 80 mm ─────────────────────────────────────────────────
            // Logo desde configuración (storage/public) con fallback a public/images/logo.png
            $logoStoragePath = Configuration::get('logo_path');
            $logoFsPath = $logoStoragePath
                ? storage_path('app/public/'.$logoStoragePath)
                : public_path('images/logo.png');
            $logo = file_exists($logoFsPath) ? base64_encode(file_get_contents($logoFsPath)) : '';

            // Mensaje al pie del ticket desde configuración
            $ticketPie = Configuration::get('ticket_pie', '¡Gracias por su preferencia!');

            // QR manual (Anexo 6) porque el twig custom no tiene qrCode()
            $qrData = implode('|', [
                $this->company->getRuc(),
                $doc->getTipoDoc(),
                $doc->getSerie(),
                $doc->getCorrelativo(),
                number_format($doc->getMtoIGV(), 2, '.', ''),
                number_format($doc->getMtoImpVenta(), 2, '.', ''),
                $doc->getFechaEmision()->format('Y-m-d'),
                $doc->getClient()->getTipoDoc(),
                $doc->getClient()->getNumDoc(),
                $hash,
            ]);

            $report = new HtmlReport(resource_path('views/pdfs'));
            $report->setTemplate('ticket.html.twig');

            return $report->render($doc, [
                'system' => [
                    'logo' => $logo,
                    'hash' => $hash,
                    'qr' => $this->generateQrBase64($qrData),
                    'sunat_status' => $payment->sunat_status ?? '',
                    'sunat_message' => $payment->sunat_message ?? '',
                    'ticket_pie' => $ticketPie,
                ],
                'user' => [
                    'extras' => $extras,
                ],
            ]);
        }

        // ── A4: DefaultTemplateResolver elige invoice/note/etc automáticamente ──
        $logoPath = public_path('images/logo.png');
        $logo = file_exists($logoPath) ? file_get_contents($logoPath) : '';

        $report = new HtmlReport;
        $resolver = new DefaultTemplateResolver;
        $report->setTemplate($resolver->getTemplate($doc));

        return $report->render($doc, [
            'system' => [
                'logo' => $logo,
                'hash' => $hash,
            ],
            'user' => [
                'header' => 'Telf: '.Configuration::get('clinica_telefono') ?: '',
                'extras' => $extras,
                'footer' => '',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function buildClient(Payment $payment, bool $isFactura): Client
    {
        $client = new Client;
        if ($isFactura) {
            $client->setTipoDoc('6')
                ->setNumDoc($payment->billing_document)
                ->setRznSocial($payment->billing_name);
        } else {
            $tipoDoc = $payment->billing_document ? '1' : '-'; // 1 = DNI
            $client->setTipoDoc($tipoDoc)
                ->setNumDoc($payment->billing_document ?: '00000000')
                ->setRznSocial(
                    $payment->billing_name
                        ?: ($payment->patient->first_name.' '.$payment->patient->last_name)
                );
        }

        return $client;
    }

    /**
     * Devuelve [baseIgv, igv, total] redondeados a 2 decimales.
     */
    private function calcularMontos(float $monto): array
    {
        $total = round($monto, 2);
        $baseIgv = round($total / 1.18, 2);
        $igv = round($total - $baseIgv, 2);

        return [$baseIgv, $igv, $total];
    }

    /**
     * Extrae el DigestValue (hash SHA-1 base64) del XML firmado.
     * Este valor es el que exige SUNAT para el QR (Anexo 6).
     */
    public function extractHashFromXml(string $xml): string
    {
        try {
            return (new XmlUtils)->getHashSign($xml);
        } catch (\Throwable) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument;
            if ($dom->loadXML($xml)) {
                $nodes = $dom->getElementsByTagNameNS(
                    'http://www.w3.org/2000/09/xmldsig#',
                    'DigestValue'
                );
                if ($nodes->length > 0) {
                    return trim($nodes->item(0)->textContent);
                }
            }

            return '';
        }
    }

    private function generateQrBase64(string $data): string
    {
        try {
            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);

            return base64_encode((new Writer($renderer))->writeString($data));
        } catch (\Throwable) {
            return '';
        }
    }

    private function numeroALetras(float $monto): string
    {
        [$entero, $decimales] = explode('.', number_format($monto, 2, '.', ''));

        return "SON {$entero} CON {$decimales}/100 SOLES";
    }

    /**
     * Obtiene el próximo correlativo disponible para una serie, basándose en
     * el máximo existente en BD o la configuración del usuario.
     */
    private function getNextCorrelativo(string $serie): string
    {
        $maxDb = Payment::where('sunat_serie', $serie)->max('sunat_correlativo');
        $maxDbInt = $maxDb ? (int) $maxDb : 0;

        $configVal = Configuration::get("correlativo_{$serie}", 1);
        $configInt = (int) $configVal;

        $next = max($maxDbInt + 1, $configInt);

        // Actualizamos la configuración para que muestre el próximo número correctamente
        Configuration::set("correlativo_{$serie}", $next + 1);

        return str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }
}
