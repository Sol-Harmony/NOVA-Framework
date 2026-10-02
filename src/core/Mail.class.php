<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class Mail      //sends plain text mails through an SMTP server ([mail] in config/config.ini), or PHP's mail() when none is set
{
    protected static $lastError = '';

    //why the last send() failed
    public static function lastError()
    {
        return self::$lastError;
    }

    //returns true when the mail server accepted the mail, false otherwise (the reason is in lastError() and the error log).
    //the visitor's address belongs into $replyTo, never into the sender: the mail would fail spam checks (SPF/DKIM)
    public static function send($to, $subject, $body, $replyTo = null, $replyToName = '')
    {
        self::$lastError = '';

        try {
            $from = Config::get('mail.from_address') ?: (Config::get('mail.username') ?: Config::get('site.business.email'));
            $fromName = Config::get('site.name', '');

            $from = self::address($from, 'mail.from_address');
            $to = self::address($to, 'recipient');

            $headers = [
                'Date: ' . date('r'),
                'From: ' . self::mailbox($from, $fromName),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . substr($from, strrpos($from, '@') + 1) . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            if ($replyTo) {
                $headers[] = 'Reply-To: ' . self::mailbox(self::address($replyTo, 'reply-to'), $replyToName);
            }

            $subject = self::encodeHeader(mb_substr($subject, 0, 150));
            //base64 keeps every character (umlauts, emojis) safe, whatever the server does with the line endings
            $body = chunk_split(base64_encode(preg_replace('/\r\n|\r|\n/', "\r\n", (string) $body)), 76, "\r\n");

            if (Config::get('mail.host')) {
                $headers[] = 'To: ' . self::mailbox($to);
                $headers[] = 'Subject: ' . $subject;
                self::smtp($from, $to, implode("\r\n", $headers) . "\r\n\r\n" . $body);
            } elseif (!@mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $from)) {
                throw new RuntimeException("PHP mail() failed. Set the [mail] section in config/config.ini to use an SMTP server.");
            }
            return true;
        } catch (\Throwable $th) {
            self::$lastError = $th->getMessage();
            error_log('Mail failed: ' . $th->getMessage());
            return false;
        }
    }

    //for a mail the server refused: never lose it, keep a copy in storage/outbox/<date>-<random>.txt where the owner can find it
    //(storage/ is not reachable from the web). any form can use this, not only the contact form
    public static function saveCopy($subject, $body)
    {
        $dir = BASEPATH . '/storage/outbox';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        self::purgeOutbox();
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.txt';
        if (@file_put_contents($file, $subject . "\n\n" . $body) === false) {
            error_log("Mail: message could not be sent and could not be saved either:\n" . $subject . "\n" . $body);
        }
    }

    //saved messages contain personal data, so they are not kept forever: files older than $days days are deleted
    public static function purgeOutbox($days = 30)
    {
        foreach (glob(BASEPATH . '/storage/outbox/*.txt') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - $days * 86400) {
                @unlink($file);
            }
        }
    }

    //a valid address or an exception. this also stops header injection: a line break can't be part of a valid address
    protected static function address($address, $what)
    {
        $address = trim((string) $address);
        if ($address === '' || preg_match('/[\s<>,;"]/', $address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid e-mail address for $what.");
        }
        return $address;
    }

    //"Name" <address>, the name is encoded when it has umlauts and cleaned from characters that could break the header
    protected static function mailbox($address, $name = '')
    {
        $name = trim(preg_replace('/[\x00-\x1F\x7F<>"\\\\]+/', ' ', (string) $name));
        if ($name === '') {
            return $address;
        }
        return self::encodeHeader($name) . ' <' . $address . '>';
    }

    //header text with umlauts and so on has to be encoded (=?UTF-8?B?...?=). line breaks are removed against header injection
    protected static function encodeHeader($text)
    {
        $text = trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $text));
        if (preg_match('/^[\x20-\x7E]*$/', $text)) {
            return $text;
        }
        return mb_encode_mimeheader($text, 'UTF-8', 'B', "\r\n");
    }

    protected static function smtp($from, $to, $message)
    {
        $host = trim((string) Config::get('mail.host'));
        $port = (int) (Config::get('mail.port') ?: 587);
        $encryption = strtolower(trim((string) Config::get('mail.encryption', 'tls')));      //tls = STARTTLS (port 587), ssl = from the start (port 465), none
        $user = (string) Config::get('mail.username', '');
        $pass = (string) Config::get('mail.password', '');
        $timeout = 10;

        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException("mail.encryption has to be tls, ssl or none.");
        }
        $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($encryption === 'none' && $user !== '' && !$local) {
            throw new RuntimeException("Refusing to send the mail password unencrypted. Use encryption = tls or ssl.");
        }

        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $socket = @stream_socket_client(($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            //a failed certificate check (implicit tls) has no error text of its own, php only logs a warning for it
            $reason = $errstr !== '' ? $errstr : trim(preg_replace('/\s+/', ' ', (string) (error_get_last()['message'] ?? '')));
            throw new RuntimeException("Could not connect to the mail server $host:$port" . ($reason !== '' ? " ($reason)" : ''));
        }
        stream_set_timeout($socket, $timeout);

        try {
            self::expect($socket, [220], 'connect');
            $me = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) gethostname()) ?: 'localhost';
            $capabilities = self::command($socket, "EHLO $me", [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $reason = trim(preg_replace('/\s+/', ' ', (string) (error_get_last()['message'] ?? '')));
                    throw new RuntimeException("Could not start an encrypted connection to the mail server" . ($reason !== '' ? ": $reason" : '.'));
                }
                $capabilities = self::command($socket, "EHLO $me", [250]);     //the server forgets everything after STARTTLS
            }

            if ($user !== '') {
                if (preg_match('/AUTH[ =].*\bLOGIN\b/i', $capabilities) || !preg_match('/AUTH[ =].*\bPLAIN\b/i', $capabilities)) {
                    self::command($socket, 'AUTH LOGIN', [334]);
                    self::command($socket, base64_encode($user), [334], true);
                    self::command($socket, base64_encode($pass), [235], true);
                } else {
                    self::command($socket, 'AUTH PLAIN ' . base64_encode("\0$user\0$pass"), [235], true);
                }
            }

            self::command($socket, "MAIL FROM:<$from>", [250]);
            self::command($socket, "RCPT TO:<$to>", [250, 251]);
            self::command($socket, 'DATA', [354]);
            fwrite($socket, preg_replace('/^\./m', '..', $message) . "\r\n.\r\n");   //a line with only a dot would end the mail, so dots at the start of a line are doubled
            self::expect($socket, [250], 'sending the message');
            @fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    //sends one SMTP command and checks the answer code. $secret: don't show the command in an error (passwords)
    protected static function command($socket, $line, array $okCodes, $secret = false)
    {
        fwrite($socket, $line . "\r\n");
        return self::expect($socket, $okCodes, $secret ? 'login' : strtok($line, ' '));
    }

    protected static function expect($socket, array $okCodes, $after)
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (!preg_match('/^\d{3}-/', $line)) {      //"250-" means more lines follow, "250 " is the last one
                break;
            }
        }
        if ($response === '') {
            throw new RuntimeException("The mail server didn't answer (timeout or connection closed) after: $after");
        }
        if (!in_array((int) substr($response, 0, 3), $okCodes, true)) {
            throw new RuntimeException("Mail server refused ($after): " . trim($response));
        }
        return $response;
    }
}
