<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReniecController extends Controller
{
    /**
     * Busca los datos de un DNI usando API de apisperu.com
     */
    public function searchDni(Request $request, $dni)
    {
        // Validar DNI
        if (strlen($dni) !== 8 || ! is_numeric($dni)) {
            return response()->json(['error' => 'DNI inválido'], 400);
        }

        $token = Configuration::get('apis_peru_token', env('APIS_PERU_TOKEN'));
        $baseUrl = Configuration::get('apis_peru_url', 'https://dniruc.apisperu.com');

        // Si no hay token configurado
        if (! $token) {
            Log::warning('Token de API no configurado para apisperu.com');

            return response()->json([
                'success' => false,
                'error' => 'Token de API no configurado',
            ], 500);
        }

        try {
            $response = Http::withoutVerifying()->get("{$baseUrl}/api/v1/dni/{$dni}?token={$token}");

            if ($response->successful()) {
                $data = $response->json();

                // Verificar si la respuesta contiene datos válidos
                if (isset($data['dni']) && isset($data['nombres'])) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'numero' => $data['dni'],
                            'nombres' => $data['nombres'],
                            'apellido_paterno' => $data['apellidoPaterno'] ?? '',
                            'apellido_materno' => $data['apellidoMaterno'] ?? '',
                            'codigo_verificacion' => $data['codVerifica'] ?? null,
                            'codigo_verificacion_letra' => $data['codVerificaLetra'] ?? null,
                            'nombre_completo' => trim(
                                ($data['apellidoPaterno'] ?? '').' '.
                                ($data['apellidoMaterno'] ?? '').' '.
                                ($data['nombres'] ?? '')
                            ),
                        ],
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'DNI no encontrado en el padrón',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error consultando DNI: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error de conexión con el servicio RENIEC',
            ], 500);
        }
    }

    /**
     * Busca los datos de un RUC usando API de apisperu.com
     */
    public function searchRuc(Request $request, $ruc)
    {
        // Validar RUC (11 dígitos)
        if (strlen($ruc) !== 11 || ! is_numeric($ruc)) {
            return response()->json(['error' => 'RUC inválido'], 400);
        }

        $token = Configuration::get('apis_peru_token', env('APIS_PERU_TOKEN'));
        $baseUrl = Configuration::get('apis_peru_url', 'https://dniruc.apisperu.com');

        // Si no hay token configurado
        if (! $token) {
            Log::warning('Token de API no configurado para apisperu.com');

            return response()->json([
                'success' => false,
                'error' => 'Token de API no configurado',
            ], 500);
        }

        try {
            $response = Http::withoutVerifying()->get("{$baseUrl}/api/v1/ruc/{$ruc}?token={$token}");

            if ($response->successful()) {
                $data = $response->json();

                // Verificar si la respuesta contiene datos válidos
                if (isset($data['ruc']) && isset($data['razonSocial'])) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'ruc' => $data['ruc'],
                            'razon_social' => $data['razonSocial'],
                            'nombre_comercial' => $data['nombreComercial'],
                            'estado' => $data['estado'],
                            'condicion' => $data['condicion'],
                            'direccion' => $data['direccion'],
                            'departamento' => $data['departamento'],
                            'provincia' => $data['provincia'],
                            'distrito' => $data['distrito'],
                            'ubigeo' => $data['ubigeo'],
                            'telefonos' => $data['telefonos'],
                            'actividades_economicas' => $data['actEconomicas'],
                            'sistema_electronica' => $data['sistElectronica'],
                            'fecha_baja' => $data['fechaBaja'] ?? null,
                            'capital' => $data['capital'] ?? null,
                        ],
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'RUC no encontrado en el padrón',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error consultando RUC: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error de conexión con el servicio SUNAT',
            ], 500);
        }
    }

    /**
     * Método genérico para buscar por DNI (mantiene compatibilidad con ruta anterior)
     */
    public function search(Request $request, $dni)
    {
        return $this->searchDni($request, $dni);
    }
}
