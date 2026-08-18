<?php
/**
 * Minimal procedural SMTP client. No libraries, no classes.
 * Sends a raw MIME message (built elsewhere) over an authenticated
 * SMTP connection -- the reliable alternative to PHP's mail(),
 * which most hosts (including Hostinger) disable or restrict.
 *
 * @param array $cfg  ['host','port','encryption' => 'ssl'|'tls'|'', 'username','password']
 * @param string $from
 * @param string $to
 * @param string $cc
 * @param string $subject
 * @param string $rawMimeBody   Full MIME body (headers for Content-Type etc. + content),
 *                               NOT including From/To/Cc/Subject/Date -- those are sent
 *                               as part of the DATA block by this function.
 * @return array ['ok' => bool, 'error' => string|null]
 */
function smtp_send_mail($cfg, $from, $to, $cc, $subject, $rawMimeBody) {
    $host = $cfg['host'];
    $port = $cfg['port'];
    $encryption = isset($cfg['encryption']) ? $cfg['encryption'] : '';
    $username = $cfg['username'];
    $password = $cfg['password'];

    $connectHost = ($encryption === 'ssl') ? 'ssl://' . $host : $host;

    $errno = 0;
    $errstr = '';
    $sock = @stream_socket_client($connectHost . ':' . $port, $errno, $errstr, 15);
    if (!$sock) {
        return ['ok' => false, 'error' => 'SMTP connect failed: ' . $errstr];
    }
    stream_set_timeout($sock, 15);

    $read = function () use ($sock) {
        $data = '';
        while ($line = fgets($sock, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };

    $write = function ($cmd) use ($sock) {
        fwrite($sock, $cmd . "\r\n");
    };

    // The SMTP envelope command (MAIL FROM) must contain a bare email
    // address only -- no display name, no extra angle brackets. The
    // display name (e.g. "Final Energy Inspection Reports <a@b.com>")
    // is fine in the message's From: header, but not here.
    $envelopeFrom = $from;
    if (preg_match('/<([^>]+)>/', $from, $m)) {
        $envelopeFrom = $m[1];
    }
    $envelopeFrom = trim($envelopeFrom);

    $expect = function ($code) use ($read, &$lastResponse) {
        $resp = $read();
        $lastResponse = $resp;
        return substr($resp, 0, 3) === (string) $code;
    };

    $lastResponse = '';
    $fail = function ($step) use (&$lastResponse, $sock) {
        fclose($sock);
        return ['ok' => false, 'error' => 'SMTP error at ' . $step . ': ' . trim($lastResponse)];
    };

    if (!$expect(220)) return $fail('connect');

    $write('EHLO ' . $host);
    if (!$expect(250)) return $fail('EHLO');

    if ($encryption === 'tls') {
        $write('STARTTLS');
        if (!$expect(220)) return $fail('STARTTLS');
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($sock);
            return ['ok' => false, 'error' => 'Could not start TLS encryption.'];
        }
        $write('EHLO ' . $host);
        if (!$expect(250)) return $fail('EHLO after STARTTLS');
    }

    $write('AUTH LOGIN');
    if (!$expect(334)) return $fail('AUTH LOGIN');

    $write(base64_encode($username));
    if (!$expect(334)) return $fail('AUTH username');

    $write(base64_encode($password));
    if (!$expect(235)) return $fail('AUTH password (check SMTP username/password in config.php)');

    $write('MAIL FROM:<' . $envelopeFrom . '>');
    if (!$expect(250)) return $fail('MAIL FROM');

    $write('RCPT TO:<' . $to . '>');
    if (!$expect(250) && !$expect(251)) return $fail('RCPT TO (recipient)');

    if (!empty($cc)) {
        $write('RCPT TO:<' . $cc . '>');
        if (!$expect(250) && !$expect(251)) return $fail('RCPT TO (cc)');
    }

    $write('DATA');
    if (!$expect(354)) return $fail('DATA');

    $headers = "From: " . $from . "\r\n";
    $headers .= "To: " . $to . "\r\n";
    if (!empty($cc)) $headers .= "Cc: " . $cc . "\r\n";
    $headers .= "Subject: " . $subject . "\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    $message = $headers . $rawMimeBody;
    // Dot-stuff lines that start with a period, per SMTP spec.
    $message = preg_replace('/\r\n\./', "\r\n..", $message);

    $write($message);
    $write('.');
    if (!$expect(250)) return $fail('message send');

    $write('QUIT');
    fclose($sock);

    return ['ok' => true, 'error' => null];
}
