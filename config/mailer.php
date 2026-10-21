<?php

/**
 * Mailer configuration
 */
return [
    'transport' => 'sendmail',

    // SMTP settings (only used when transport = "smtp")
    'host'       => '127.0.0.1',
    'port'       => 25,
    'username'   => '',
    'password'   => '',
    'encryption' => null,   // tls, ssl, or null
];
