<?php

namespace Foolz\FoolFrame\Model;

/**
 * Uses PHP's built-in mail() function.
 */
class Mailer extends Model
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->config = $context->getService('config');
    }

    /**
     * Create a simple message object.
     */
    public function create()
    {
        return new class {
            public $to = [];
            public $from = [];
            public $subject = '';
            public $body = '';

            public function setTo($email, $name = null)
            {
                $this->to[$email] = $name;
                return $this;
            }

            public function setFrom($email, $name = null)
            {
                $this->from[$email] = $name;
                return $this;
            }

            public function setSubject($subject)
            {
                $this->subject = $subject;
                return $this;
            }

            public function setBody($body)
            {
                $this->body = $body;
                return $this;
            }

            public function getTo() { return $this->to; }
            public function getFrom() { return $this->from; }
            public function getSubject() { return $this->subject; }
            public function getBody() { return $this->body; }
        };
    }

    /**
     * Send using PHP's mail() function.
     * Supports the old swiftmailer config format for compatibility.
     */
    public function send($message)
    {
        $config = $this->config->get('foolz/foolframe', 'swiftmailer')
               ?: $this->config->get('foolz/foolframe', 'mailer')
               ?: ['transport' => 'sendmail'];

        $to      = $message->getTo();
        $from    = $message->getFrom();
        $subject = $message->getSubject();
        $body    = $message->getBody();

        $headers = [];
        if (!empty($from)) {
            $fromEmail = key($from);
            $fromName  = current($from) ?: '';
            $headers[] = 'From: ' . ($fromName ? "$fromName <$fromEmail>" : $fromEmail);
        }
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        $toList = [];
        foreach ($to as $email => $name) {
            $toList[] = $name ? "$name <$email>" : $email;
        }

        return mail(
            implode(', ', $toList),
            $subject,
            $body,
            implode("\r\n", $headers)
        );
    }
}
