<?php
/**
 * Harici kütüphane gerektirmeyen basit SMTP istemcisi.
 * Hosting firmasının verdiği e-posta hesabı (örn. info@alanadiniz.com) ile kimlik doğrulamalı gönderim yapar.
 * Ayarlar: Yönetim > Site & Ödeme Ayarları > E-posta (SMTP)
 */

function smtp_send(array $cfg, string $to, string $subject, string $html, string $fromName, string $fromEmail): bool
{
    $host = $cfg['host'];
    $port = (int) $cfg['port'];
    $secure = $cfg['secure']; // ssl | tls | none
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        throw new RuntimeException("SMTP sunucusuna bağlanılamadı: $errstr ($errno)");
    }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $c, array $ok) use ($fp, $read): string {
        if ($c !== '') fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!in_array((int) substr($r, 0, 3), $ok, true)) {
            throw new RuntimeException('SMTP hatası: ' . trim($r));
        }
        return $r;
    };

    $ehloHost = parse_url(config('base_url'), PHP_URL_HOST) ?: 'localhost';
    $cmd('', [220]);
    $cmd('EHLO ' . $ehloHost, [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            throw new RuntimeException('TLS başlatılamadı.');
        }
        $cmd('EHLO ' . $ehloHost, [250]);
    }
    if ($cfg['user'] !== '') {
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode($cfg['user']), [334]);
        $cmd(base64_encode($cfg['pass']), [235]);
    }
    $cmd('MAIL FROM:<' . $fromEmail . '>', [250]);
    $cmd('RCPT TO:<' . $to . '>', [250, 251]);
    $cmd('DATA', [354]);

    $headers = [
        'Date: ' . date('r'),
        'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Reply-To: ' . $fromEmail,
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $ehloHost . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $body = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n.";
    $cmd($body, [250]);
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}
