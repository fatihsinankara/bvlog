<?php

namespace App\Controllers;

use Core\Controller;
use Core\Cron;
use Core\Request;
use Core\Response;

class CronController extends Controller
{
    /**
     * GET /cron?token=...
     * Harici Cron servisleri veya cPanel cURL/Wget komutları için uç nokta.
     */
    public function handle(): void
    {
        $providedToken = (string) (Request::get('token', '') ?: ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
        $expectedToken = Cron::getToken();

        if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            Response::json([
                'success' => false,
                'error'   => 'Geçersiz veya eksik Cron güvenlik anahtarı (token).',
            ], 403);
        }

        $result = Cron::run(false, null, 'http');
        Response::json($result);
    }
}
