<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $config = class_exists(Configuration::class) ? Configuration::all() : collect([]);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'roles' => $request->user()->roles->pluck('name'),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ]) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sunatConfig' => [
                'active' => $config->where('key', 'sunat_active')->first()?->value === '1',
            ],
            'evolutionConfig' => [
                'url' => $config->where('key', 'whatsapp_api_url')->first()?->value ?? null,
                'apiKey' => $config->where('key', 'whatsapp_api_key')->first()?->value ?? null,
                'instance' => $config->where('key', 'whatsapp_instance')->first()?->value ?? null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'new_payment_id' => fn () => $request->session()->get('new_payment_id'),
                'auto_print' => fn () => $request->session()->get('auto_print'),
            ],
        ];
    }
}
