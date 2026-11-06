<?php

namespace Core;

class Security
{
    /**
     * Cloudflare yayın aralıkları (cloudflare.com/ips-v4 ve ips-v6).
     * Yalnızca TCP tarafı bu aralıktaysa CF-Connecting-IP kabul edilir.
     */
    private const CLOUDFLARE_CIDRS = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com https://www.google.com; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://open.spotify.com https://codepen.io https://platform.twitter.com https://challenges.cloudflare.com https://www.google.com");
    }

    public static function escape(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function getClientIp(): string
    {
        return self::resolveClientIp($_SERVER);
    }

    /**
     * @param array<string, mixed> $server
     * @param list<string>|null $trustedCidrs
     */
    public static function resolveClientIp(array $server, ?array $trustedCidrs = null): string
    {
        $remote = (string) ($server['REMOTE_ADDR'] ?? '');
        if (!filter_var($remote, FILTER_VALIDATE_IP)) {
            $remote = '0.0.0.0';
        }

        $cidrs = $trustedCidrs ?? self::trustedCidrs();
        if (!self::ipInCidrs($remote, $cidrs)) {
            return $remote;
        }

        if (!empty($server['HTTP_CF_CONNECTING_IP']) && self::ipInCidrs($remote, self::CLOUDFLARE_CIDRS)) {
            $cfIp = trim((string) $server['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            $parts = array_map('trim', explode(',', (string) $server['HTTP_X_FORWARDED_FOR']));
            for ($i = count($parts) - 1; $i >= 0; $i--) {
                $candidate = $parts[$i];
                if (!filter_var($candidate, FILTER_VALIDATE_IP)) {
                    continue;
                }
                if (!self::ipInCidrs($candidate, $cidrs)) {
                    return $candidate;
                }
            }
        }

        return $remote;
    }

    /**
     * @param list<string> $cidrs
     */
    public static function ipInCidrs(string $ip, array $cidrs): bool
    {
        $ipBin = @inet_pton($ip);
        if ($ipBin === false) {
            return false;
        }

        foreach ($cidrs as $cidr) {
            $cidr = trim($cidr);
            if ($cidr === '') {
                continue;
            }
            if (!str_contains($cidr, '/')) {
                $single = @inet_pton($cidr);
                if ($single !== false && $single === $ipBin) {
                    return true;
                }
                continue;
            }

            [$subnet, $bitsRaw] = explode('/', $cidr, 2);
            $subBin = @inet_pton($subnet);
            $bits = (int) $bitsRaw;
            if ($subBin === false || strlen($subBin) !== strlen($ipBin)) {
                continue;
            }
            $maxBits = strlen($ipBin) * 8;
            if ($bits < 0 || $bits > $maxBits) {
                continue;
            }
            $bytes = intdiv($bits, 8);
            $rem = $bits % 8;
            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
                continue;
            }
            if ($rem === 0) {
                return true;
            }
            $mask = (0xFF << (8 - $rem)) & 0xFF;
            if ((ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function trustedCidrs(): array
    {
        $cidrs = self::CLOUDFLARE_CIDRS;
        if (!function_exists('setting') || !function_exists('is_installed') || !is_installed()) {
            return $cidrs;
        }

        $raw = (string) setting('trusted_proxies', '');
        foreach (preg_split('/[\s,]+/', $raw) ?: [] as $item) {
            $item = trim($item);
            if ($item !== '' && preg_match('/^[0-9a-fA-F:.]+(\/\d{1,3})?$/', $item)) {
                $cidrs[] = $item;
            }
        }
        return $cidrs;
    }
}
