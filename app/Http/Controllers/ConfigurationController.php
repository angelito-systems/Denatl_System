<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigurationController extends Controller
{
    public function index()
    {
        $configs = Configuration::pluck('value', 'key')->toArray();

        return Inertia::render('Configuracion/Index', [
            'configs' => $configs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array'
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Configuration::set($key, $value);
        }

        return redirect()->back()->with('success', 'Configuración guardada exitosamente.');
    }

    public function signQzTray(Request $request)
    {
        $toSign = $request->query('request');
        $privateKeyStr = Configuration::get('qztray_private_key_pem');

        if (!$privateKeyStr || !$toSign) {
            return response()->json(['error' => 'No private key configured or missing request.'], 400);
        }

        // Clean up the key string
        $privateKeyStr = str_replace("\r\n", "\n", trim($privateKeyStr));
        $privateKey = openssl_get_privatekey($privateKeyStr);

        if (!$privateKey) {
            return response()->json(['error' => 'Invalid private key format.'], 400);
        }

        $signature = '';
        // Try SHA512 first (Standard for newer QZ Tray certs)
        if (openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512)) {
            return response(base64_encode($signature))->header('Content-Type', 'text/plain');
        }

        // Fallback to SHA1 for older QZ Tray certs
        if (openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
            return response(base64_encode($signature))->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Failed to sign the request.'], 500);
    }

    public function getQzTrayCert()
    {
        $cert = Configuration::get('qztray_cert_txt');
        if (!$cert) {
            return response('No certificate', 404);
        }
        return response($cert)->header('Content-Type', 'text/plain');
    }
}
