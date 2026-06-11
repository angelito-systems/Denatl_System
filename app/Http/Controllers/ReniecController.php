<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReniecController extends Controller
{
    /**
     * Busca los datos de un DNI usando una API de terceros (ej: apis.net.pe).
     */
    public function search(Request $request, $dni)
    {
        if (strlen($dni) !== 8 || !is_numeric($dni)) {
            return response()->json(['error' => 'DNI inválido'], 400);
        }

        $token = config('services.apis_net_pe.token', env('APIS_NET_PE_TOKEN'));

        // Si no hay token configurado, simulamos datos reales para pruebas locales
        if (!$token) {
            Log::info("Buscando DNI {$dni} (MOCK - Sin token API)");
            // Simulated realistic data
            return response()->json([
                'success' => true,
                'data' => [
                    'numero' => $dni,
                    'nombres' => 'JUAN CARLOS',
                    'apellido_paterno' => 'PEREZ',
                    'apellido_materno' => 'GOMEZ',
                    'nombre_completo' => 'PEREZ GOMEZ JUAN CARLOS'
                ]
            ]);
        }

        try {
            $response = Http::withToken($token)
                ->get("https://api.apis.net.pe/v2/reniec/dni", [
                    'numero' => $dni
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'data' => [
                        'numero' => $data['numero'] ?? $dni,
                        'nombres' => $data['nombres'] ?? '',
                        'apellido_paterno' => $data['apellidoPaterno'] ?? '',
                        'apellido_materno' => $data['apellidoMaterno'] ?? '',
                        'nombre_completo' => ($data['nombres'] ?? '') . ' ' . ($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? '')
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'DNI no encontrado en el padrón o error de API.'
            ], 404);

        } catch (\Exception $e) {
            Log::error("Error consultando DNI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión con el servicio RENIEC.'
            ], 500);
        }
    }
}
