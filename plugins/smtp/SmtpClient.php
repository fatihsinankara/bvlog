<?php

namespace Plugins\Smtp;

use App\Models\Setting;

class SmtpClient
{
    private array $config;
    private array $log = [];
    private float $startTime = 0.0;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: self::getDefaultConfig();
    }

    public static function getDefaultConfig(): array
    {
        return [
            'enabled'        => setting('smtp_enabled', '0') === '1',
            'host'           => setting('smtp_host', 'smtp.gmail.com'),
            'port'           => (int) setting('smtp_port', 587),
            'encryption'     => setting('smtp_encryption', 'tls'), // tls, ssl, none
            'username'       => setting('smtp_username', ''),
            'password'       => setting('smtp_password', ''),
            'from_email'     => setting('smtp_from_email', setting('social_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))),
            'from_name'      => setting('smtp_from_name', setting('site_title', 'Bvlog')),
            'timeout'        => (int) setting('smtp_timeout', 15),
            'notify_contact' => setting('smtp_notify_contact', '0') === '1',
            'admin_email'    => setting('smtp_admin_email', setting('social_email', '')),
        ];
    }

    public function getLog(): array
    {
        return $this->log;
    }

    private function addLog(string $type, string $message): void
    {
        $now = microtime(true);
        $elapsed = $this->startTime > 0 ? round(($now - $this->startTime) * 1000) : 0;
        $this->log[] = [
            'time'    => date('H:i:s') . '.' . sprintf('%03d', ($now - floor($now)) * 1000),
            'elapsed' => "{$elapsed}ms",
            'type'    => $type, // 'client', 'server', 'info', 'error', 'success'
            'message' => $message,
        ];
    }

    /**
     * Bağlantıyı test eder ve alıcıya test e-postası gönderir.
     */
    public function testConnection(string $toEmail): array
    {
        $this->log = [];
        $this->startTime = microtime(true);

        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success'     => false,
                'message'     => 'Geçersiz test e-posta adresi belirtildi.',
                'duration_ms' => 0,
                'log'         => [],
            ];
        }

        $siteTitle = setting('site_title', 'Bvlog');
        $subject = "Bvlog SMTP Bağlantı Testi — " . date('d.m.Y H:i');
        
        $htmlBody = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #e4e4e7; border-radius: 12px; background: #ffffff; color: #18181b;">
    <div style="margin-bottom: 20px; border-bottom: 1px solid #f4f4f5; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 20px; color: #10b981;">✓ SMTP Bağlantısı Başarılı!</h2>
        <p style="margin: 4px 0 0; font-size: 13px; color: #71717a;">Bu e-posta, {$siteTitle} yönetim panelinden tetiklenen test gönderimidir.</p>
    </div>
    <div style="background: #f4f4f5; border-radius: 8px; padding: 16px; font-size: 13px; line-height: 1.6; margin-bottom: 20px;">
        <div><strong>Sunucu (Host):</strong> {$this->config['host']}</div>
        <div><strong>Port & Şifreleme:</strong> {$this->config['port']} ({$this->config['encryption']})</div>
        <div><strong>Gönderen:</strong> {$this->config['from_name']} &lt;{$this->config['from_email']}&gt;</div>
        <div><strong>Zaman Damgası:</strong> {$this->startTime}</div>
    </div>
    <p style="font-size: 12px; color: #a1a1aa; margin: 0;">Bu e-postayı görüyorsanız Bvlog SMTP yapılandırmanız eksiksiz ve kullanıma hazırdır.</p>
</div>
HTML;

        try {
            $this->addLog('info', "SMTP Testi başlatıldı: {$this->config['host']}:{$this->config['port']} ({$this->config['encryption']})");
            $this->executeSmtp([$toEmail], $subject, $htmlBody);
            
            $duration = round((microtime(true) - $this->startTime) * 1000);
            $this->addLog('success', "E-posta başarıyla iletildi! Toplam süre: {$duration}ms");

            return [
                'success'     => true,
                'message'     => "SMTP bağlantısı ve e-posta gönderimi başarılı! ({$toEmail} adresine iletildi, yanıt süresi: {$duration}ms)",
                'duration_ms' => $duration,
                'log'         => $this->log,
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $this->startTime) * 1000);
            $this->addLog('error', "Hata: " . $e->getMessage());

            return [
                'success'     => false,
                'message'     => $e->getMessage(),
                'duration_ms' => $duration,
                'log'         => $this->log,
            ];
        }
    }

    /**
     * E-posta gönderir (SMTP üzerinden veya yerel mail() yedeği ile).
     */
    public function send(string|array $to, string $subject, string $htmlBody, array $options = []): bool
    {
        $recipients = is_array($to) ? $to : [$to];
        $recipients = array_filter(array_map('trim', $recipients), fn($em) => filter_var($em, FILTER_VALIDATE_EMAIL));

        if (empty($recipients)) {
            return false;
        }

        if (empty($this->config['enabled'])) {
            // SMTP aktif değilse PHP yerel mail() fonksiyonunu dene
            return $this->sendViaPhpMail($recipients, $subject, $htmlBody, $options);
        }

        try {
            $this->log = [];
            $this->startTime = microtime(true);
            $this->executeSmtp($recipients, $subject, $htmlBody, $options);
            return true;
        } catch (\Throwable $e) {
            error_log("Bvlog SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Yerel PHP mail() fonksiyonu ile gönderim yedeği.
     */
    private function sendViaPhpMail(array $recipients, string $subject, string $htmlBody, array $options = []): bool
    {
        $fromEmail = $options['from_email'] ?? $this->config['from_email'];
        $fromName  = $options['from_name'] ?? $this->config['from_name'];

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . ($options['reply_to'] ?? $fromEmail),
            'X-Mailer: Bvlog/' . setting('app_version', '1.2.3'),
        ];

        $encodedSubject = $this->encodeHeader($subject);
        $toStr = implode(', ', $recipients);

        return @mail($toStr, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Saf PHP Soket (stream_socket_client) üzerinden SMTP konuşması.
     */
    private function executeSmtp(array $recipients, string $subject, string $htmlBody, array $options = []): void
    {
        $host       = $this->config['host'];
        $port       = (int) $this->config['port'];
        $encryption = strtolower((string) ($this->config['encryption'] ?? 'tls'));
        $timeout    = max(5, (int) ($this->config['timeout'] ?? 15));

        // 1. Hedef Adresi Belirleme
        $target = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ]
        ]);

        $this->addLog('client', "Sunucuya bağlanılıyor: {$target} (Zaman aşımı: {$timeout}s)");

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            throw new \RuntimeException("SMTP sunucusuna bağlanılamadı ({$host}:{$port}): [{$errno}] {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        try {
            // Sunucu karşılama bannerını oku (220 beklenir)
            $reply = $this->readReply($socket);
            if (!str_starts_with($reply, '220')) {
                throw new \RuntimeException("Geçersiz sunucu yanıtı (220 bekleniyordu): {$reply}");
            }

            // EHLO gönder
            $clientHost = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $clientHost = preg_replace('/:[0-9]+$/', '', (string) $clientHost) ?: 'localhost';
            $this->sendCommand($socket, "EHLO {$clientHost}");
            $ehloReply = $this->readReply($socket);

            if (!str_starts_with($ehloReply, '250')) {
                // EHLO desteklenmiyorsa HELO dene
                $this->sendCommand($socket, "HELO {$clientHost}");
                $this->expectReply($socket, '250');
            }

            // STARTTLS Başlatma
            if ($encryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS');
                $this->expectReply($socket, '220');

                $this->addLog('info', "TLS el sıkışması (Crypto Handshake) başlatılıyor...");
                
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }

                $cryptoSuccess = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if (!$cryptoSuccess) {
                    throw new \RuntimeException("STARTTLS şifreleme el sıkışması başarısız oldu. Sunucu sertifikası veya port ayarlarını kontrol edin.");
                }

                $this->addLog('info', "TLS el sıkışması başarıyla tamamlandı. Şifreli tünel aktif.");

                // STARTTLS sonrası tekrar EHLO gönderilmeli
                $this->sendCommand($socket, "EHLO {$clientHost}");
                $this->expectReply($socket, '250');
            }

            // Kimlik Doğrulama (AUTH LOGIN)
            $username = trim((string) ($this->config['username'] ?? ''));
            $password = trim((string) ($this->config['password'] ?? ''));

            if ($username !== '' && $password !== '') {
                $this->sendCommand($socket, 'AUTH LOGIN');
                $this->expectReply($socket, '334');

                // Kullanıcı Adı (Base64)
                $this->sendCommand($socket, base64_encode($username), false);
                $this->addLog('client', "Kullanıcı Adı Gönderildi: {$username}");
                $this->expectReply($socket, '334');

                // Şifre (Base64)
                $this->sendCommand($socket, base64_encode($password), false);
                $this->addLog('client', "Şifre Gönderildi: [*** GÜVENLİK İÇİN GİZLENDİ ***]");
                
                $authReply = $this->readReply($socket);
                if (!str_starts_with($authReply, '235')) {
                    throw new \RuntimeException("Kimlik doğrulama başarısız (Şifre veya Kullanıcı Adı Hatalı): {$authReply}");
                }
                $this->addLog('info', "Kimlik doğrulama başarılı (AUTH 235).");
            }

            // MAIL FROM
            $fromEmail = $options['from_email'] ?? $this->config['from_email'];
            $fromName  = $options['from_name'] ?? $this->config['from_name'];

            $this->sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            $this->expectReply($socket, '250');

            // RCPT TO (Tüm alıcılar için)
            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient}>");
                $this->expectReply($socket, '250');
            }

            // DATA
            $this->sendCommand($socket, 'DATA');
            $this->expectReply($socket, '354');

            // Başlıklar ve İçerik İnşası (RFC 5322)
            $boundary = '=_bvlog_' . md5(uniqid((string) time(), true));
            $msgId = '<' . md5(uniqid(microtime(), true)) . '@' . $clientHost . '>';

            $headers = [];
            $headers[] = 'Date: ' . date('r');
            $headers[] = 'To: ' . implode(', ', array_map(fn($r) => "<{$r}>", $recipients));
            $headers[] = 'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>";
            if (!empty($options['reply_to'])) {
                $headers[] = 'Reply-To: <' . $options['reply_to'] . '>';
            }
            $headers[] = 'Subject: ' . $this->encodeHeader($subject);
            $headers[] = 'Message-ID: ' . $msgId;
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'X-Mailer: Bvlog SMTP Client/1.2.3';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';

            $rawMessage = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";

            $this->addLog('client', "E-posta içeriği iletiliyor (" . strlen($htmlBody) . " bayt)...");
            fwrite($socket, $rawMessage . "\r\n");

            $dataReply = $this->readReply($socket);
            if (!str_starts_with($dataReply, '250')) {
                throw new \RuntimeException("E-posta veri aktarımı reddedildi: {$dataReply}");
            }

            // QUIT
            $this->sendCommand($socket, 'QUIT');
            $this->readReply($socket);
        } finally {
            @fclose($socket);
        }
    }

    private function sendCommand($socket, string $command, bool $log = true): void
    {
        if ($log) {
            $this->addLog('client', $command);
        }
        fwrite($socket, $command . "\r\n");
    }

    private function readReply($socket): string
    {
        $reply = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $reply .= $line;
            // Çok satırlı SMTP yanıtlarında 4. karakter boşluksa yanıt bitti demektir (örn: "250 OK")
            // 4. karakter tire ise devamı var demektir (örn: "250-SIZE")
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $trimmed = trim($reply);
        if ($trimmed !== '') {
            $this->addLog('server', $trimmed);
        }

        return $trimmed;
    }

    private function expectReply($socket, string $expectedCode): string
    {
        $reply = $this->readReply($socket);
        if (!str_starts_with($reply, $expectedCode)) {
            throw new \RuntimeException("Beklenmeyen SMTP sunucu yanıtı ({$expectedCode} bekleniyordu): {$reply}");
        }
        return $reply;
    }

    private function encodeHeader(string $text): string
    {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }
}
