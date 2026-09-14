<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\MasterLogKunjungan;
use Illuminate\Support\Str;

class TrackPageVisits
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Track only successful public GET requests
        if ($request->isMethod('GET') && !$request->ajax()) {
            $path = $request->path();

            // Skip admin, api, asset, and system routes
            if (!Str::startsWith($path, 'admin') && 
                !Str::startsWith($path, 'api') && 
                !Str::startsWith($path, '_') &&
                !Str::startsWith($path, 'css') &&
                !Str::startsWith($path, 'js') &&
                !Str::startsWith($path, 'images')) {

                $pageName = $this->resolvePageName($path);
                $userAgent = $request->userAgent() ?? '';
                $deviceType = $this->resolveDeviceType($userAgent);

                try {
                    MasterLogKunjungan::create([
                        'nama_halaman' => $pageName,
                        'url' => '/' . ltrim($request->getRequestUri(), '/'),
                        'method' => $request->method(),
                        'ip_address' => $request->ip() ?? '127.0.0.1',
                        'user_agent' => substr($userAgent, 0, 255),
                        'device_type' => $deviceType,
                    ]);
                } catch (\Exception $e) {
                    // Suppress tracking failures to avoid impacting page response
                }
            }
        }

        return $response;
    }

    private function resolvePageName(string $path): string
    {
        if ($path === '/' || $path === '') return 'Home Landing Page';
        if (Str::startsWith($path, 'products')) return 'Product Catalog & Brands';
        if (Str::startsWith($path, 'about')) return 'About Us';
        if (Str::startsWith($path, 'businesses')) return 'Business Units';
        if (Str::startsWith($path, 'news')) return 'News & Articles';
        if (Str::startsWith($path, 'careers')) return 'Careers & Jobs';
        if (Str::startsWith($path, 'contact')) return 'Contact Us';
        if (Str::startsWith($path, 'downloads')) return 'Downloads Center';
        if (Str::startsWith($path, 'store')) return 'Online Store';

        return 'Halaman /' . $path;
    }

    private function resolveDeviceType(string $userAgent): string
    {
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/(Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini)/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
