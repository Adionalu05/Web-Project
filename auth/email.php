<?php

/**
 * Send an email using PHP mail().
 *
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param string|null $from
 * @return bool
 */
function sendEmail($to, $subject, $body, $from = null) {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';

    if ($from) {
        $headers[] = 'From: ' . $from;
    }

    return mail($to, $subject, $body, implode("\r\n", $headers));
}
