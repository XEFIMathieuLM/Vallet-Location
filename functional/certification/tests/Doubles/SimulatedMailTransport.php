<?php

namespace Functional\Certification\Tests\Doubles;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

final class SimulatedMailTransport extends AbstractTransport
{
    public const ACCEPTS = 'accepts';

    public const UNAVAILABLE = 'unavailable';

    public const REJECTS_RECIPIENT = 'rejects_recipient';

    public static string $mode = self::ACCEPTS;

    /**
     * @var list<SentMessage>
     */
    public static array $sentMessages = [];

    protected function doSend(SentMessage $message): void
    {
        match (self::$mode) {
            self::UNAVAILABLE => throw new TransportException('Connection could not be established with host "mail.vallet.test:587".'),
            self::REJECTS_RECIPIENT => throw new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 Mailbox unavailable".', 550),
            default => self::$sentMessages[] = $message,
        };
    }

    public function __toString(): string
    {
        return 'simulated://default';
    }
}
