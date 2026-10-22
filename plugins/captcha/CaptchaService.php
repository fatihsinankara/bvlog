<?php

namespace Plugins\Captcha;

use App\Models\Setting;
use Core\Request;
use Core\Security;

class CaptchaService
{
    public const PROVIDER_OFF = 'off';
    public const PROVIDER_CLOUDFLARE = 'cloudflare';
    public const PROVIDER_RECAPTCHA = 'recaptcha';

    public static function getConfig(): array
    {
        $provider = Setting::get('captcha_provider', self::PROVIDER_OFF);
        if (!in_array($provider, [self::PROVIDER_OFF, self::PROVIDER_CLOUDFLARE, self::PROVIDER_RECAPTCHA], true)) {
            $provider = self::PROVIDER_OFF;
        }

        $forms = Setting::getJson('captcha_forms', ['login', 'contact', 'comment', 'forgot_password']);
        if (!is_array($forms)) {
            $forms = ['login', 'contact', 'comment', 'forgot_password'];
        }

        return [
            'provider'      => $provider,
            'cf_site_key'   => (string) Setting::get('captcha_cf_site_key', ''),
            'cf_secret_key' => (string) Setting::get('captcha_cf_secret_key', ''),
            'rc_site_key'   => (string) Setting::get('captcha_rc_site_key', ''),
            'rc_secret_key' => (string) Setting::get('captcha_rc_secret_key', ''),
            'forms'         => $forms,
            'theme'         => (string) Setting::get('captcha_theme', 'auto'),
        ];
    }

    public static function isFormEnabled(string $form): bool
    {
        $config = self::getConfig();
        $provider = $config['provider'];

        if ($provider === self::PROVIDER_OFF) {
            return false;
        }

        if (!in_array($form, $config['forms'], true)) {
            return false;
        }

        if ($provider === self::PROVIDER_CLOUDFLARE) {
            return $config['cf_site_key'] !== '' && $config['cf_secret_key'] !== '';
        }

        if ($provider === self::PROVIDER_RECAPTCHA) {
            return $config['rc_site_key'] !== '' && $config['rc_secret_key'] !== '';
        }

        return false;
    }

    public static function render(string $form): string
    {
        if (!self::isFormEnabled($form)) {
            return '';
        }

        $config = self::getConfig();
        $provider = $config['provider'];
        $theme = $config['theme'] ?: 'auto';

        if ($provider === self::PROVIDER_CLOUDFLARE) {
            $siteKey = htmlspecialchars($config['cf_site_key'], ENT_QUOTES, 'UTF-8');
            return <<<HTML
<div class="bvlog-captcha-box my-3">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <div class="cf-turnstile" data-sitekey="{$siteKey}" data-theme="{$theme}"></div>
</div>
HTML;
        }

        if ($provider === self::PROVIDER_RECAPTCHA) {
            $siteKey = htmlspecialchars($config['rc_site_key'], ENT_QUOTES, 'UTF-8');
            $rcTheme = ($theme === 'dark') ? 'dark' : 'light';
            return <<<HTML
<div class="bvlog-captcha-box my-3">
    <script src="https://www.google.com/recaptcha/api.js?hl=tr" async defer></script>
    <div class="g-recaptcha" data-sitekey="{$siteKey}" data-theme="{$rcTheme}"></div>
</div>
HTML;
        }

        return '';
    }

    public static function verify(
        string $form,
        ?string $token = null,
        ?string $overrideSecret = null,
        ?string $overrideProvider = null
    ): array {
        $config = self::getConfig();
        $provider = $overrideProvider ?: $config['provider'];

        // Eğer zorlama/test değilse ve bu form için aktif değilse atla
        if ($form !== 'test' && !self::isFormEnabled($form)) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Bu form için CAPTCHA aktif değil.',
            ];
        }

        $clientIp = Security::getClientIp();
        $start = microtime(true);

        if ($provider === self::PROVIDER_CLOUDFLARE) {
            $secret = $overrideSecret ?: $config['cf_secret_key'];
            $token = $token ?: (Request::post('cf-turnstile-response') ?? '');

            if ($token === '') {
                return [
                    'success'  => false,
                    'provider' => 'cloudflare',
                    'error'    => 'Lütfen Cloudflare güvenlik doğrulamasını tamamlayın.',
                ];
            }

            if ($secret === '') {
                return [
                    'success'  => false,
                    'provider' => 'cloudflare',
                    'error'    => 'Cloudflare Secret Key tanımlanmamış.',
                ];
            }

            $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $params = [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $clientIp,
            ];

            $res = self::sendPostRequest($endpoint, $params);
            $latency = round((microtime(true) - $start) * 1000, 1);

            if ($res === null) {
                return [
                    'success'    => false,
                    'provider'   => 'cloudflare',
                    'latency_ms' => $latency,
                    'error'      => 'Cloudflare doğrulama sunucusuna bağlanılamadı.',
                ];
            }

            $success = !empty($res['success']);
            $errorMsg = null;
            if (!$success) {
                $codes = $res['error-codes'] ?? [];
                $errorMsg = self::formatErrorCodes($codes, 'Cloudflare Turnstile');
            }

            return [
                'success'    => $success,
                'provider'   => 'cloudflare',
                'latency_ms' => $latency,
                'error'      => $errorMsg,
                'raw'        => $res,
            ];
        }

        if ($provider === self::PROVIDER_RECAPTCHA) {
            $secret = $overrideSecret ?: $config['rc_secret_key'];
            $token = $token ?: (Request::post('g-recaptcha-response') ?? '');

            if ($token === '') {
                return [
                    'success'  => false,
                    'provider' => 'recaptcha',
                    'error'    => 'Lütfen "Ben robot değilim" kutucuğunu onaylayın.',
                ];
            }

            if ($secret === '') {
                return [
                    'success'  => false,
                    'provider' => 'recaptcha',
                    'error'    => 'Google reCAPTCHA Secret Key tanımlanmamış.',
                ];
            }

            $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
            $params = [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $clientIp,
            ];

            $res = self::sendPostRequest($endpoint, $params);
            $latency = round((microtime(true) - $start) * 1000, 1);

            if ($res === null) {
                return [
                    'success'    => false,
                    'provider'   => 'recaptcha',
                    'latency_ms' => $latency,
                    'error'      => 'Google reCAPTCHA sunucusuna bağlanılamadı.',
                ];
            }

            $success = !empty($res['success']);
            $errorMsg = null;
            if (!$success) {
                $codes = $res['error-codes'] ?? [];
                $errorMsg = self::formatErrorCodes($codes, 'Google reCAPTCHA');
            }

            return [
                'success'    => $success,
                'provider'   => 'recaptcha',
                'latency_ms' => $latency,
                'error'      => $errorMsg,
                'raw'        => $res,
            ];
        }

        return [
            'success' => true,
            'skipped' => true,
        ];
    }

    private static function sendPostRequest(string $url, array $params): ?array
    {
        $postFields = http_build_query($params);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_USERAGENT      => 'Bvlog-Captcha-Verifier/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $body = curl_exec($ch);
            $errno = curl_errno($ch);

            // SSL sertifika sorunu durumunda güvenli yedek deneme
            if (($errno === 60 || $errno === 77) && !$body) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                $body = curl_exec($ch);
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($body) && $body !== '') {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        // cURL başarısız olursa stream context dene
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Bvlog-Captcha-Verifier/1.0\r\n",
                'content' => $postFields,
                'timeout' => 8,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if (is_string($body) && $body !== '') {
            $data = json_decode($body, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    private static function formatErrorCodes(array $codes, string $providerName): string
    {
        if (empty($codes)) {
            return "{$providerName} doğrulaması başarısız oldu.";
        }

        $messages = [];
        foreach ($codes as $code) {
            $messages[] = match ($code) {
                'missing-input-secret'   => 'Gizli anahtar (Secret Key) girilmemiş.',
                'invalid-input-secret'   => 'Girilen Gizli Anahtar (Secret Key) geçersiz veya hatalı.',
                'missing-input-response' => 'Kullanıcı yanıtı (token) eksik.',
                'invalid-input-response' => 'Güvenlik yanıtı geçersiz veya süresi dolmuş.',
                'bad-request'            => 'Sunucuya gönderilen istek biçimi hatalı.',
                'timeout-or-duplicate'   => 'Bu doğrulama yanıtının süresi dolmuş veya daha önce kullanılmış.',
                default                  => "Hata kodu: {$code}",
            };
        }

        return implode(' ', $messages);
    }
}
