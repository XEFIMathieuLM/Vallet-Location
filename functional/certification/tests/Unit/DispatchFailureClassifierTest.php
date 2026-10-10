<?php

namespace Functional\Certification\Tests\Unit;

use Functional\Certification\Dispatches\DispatchFailureClassifier;
use Functional\Certification\Enums\DispatchFailureReason;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Tests\TestCase;
use Throwable;

class DispatchFailureClassifierTest extends TestCase
{
    /**
     * @return iterable<string, array{Throwable, DispatchFailureReason|null}>
     */
    public static function failures(): iterable
    {
        yield 'adresse invalide' => [new RfcComplianceException('Email "pas-une-adresse" does not comply with addr-spec of RFC 2822.'), DispatchFailureReason::InvalidAddress];
        yield '550 par code' => [new TransportException('Mailbox unavailable', 550), DispatchFailureReason::RecipientRejected];
        yield '551 dans le message' => [new TransportException('Expected response code "250" but got code "551", with message "551 User not local".'), DispatchFailureReason::RecipientRejected];
        yield '553 dans le message' => [new TransportException('got code "553", with message "553 Mailbox name not allowed".'), DispatchFailureReason::RecipientRejected];
        yield '554 par code' => [new TransportException('Transaction failed', 554), DispatchFailureReason::RecipientRejected];
        yield 'connexion impossible' => [new TransportException('Connection could not be established with host "mail.vallet.test:587".'), DispatchFailureReason::MailServiceUnavailable];
        yield '421 temporaire' => [new TransportException('got code "421", with message "421 Service not available".', 421), DispatchFailureReason::MailServiceUnavailable];
        yield '450 temporaire' => [new TransportException('Mailbox busy', 450), DispatchFailureReason::MailServiceUnavailable];
        yield 'autre exception' => [new RuntimeException('Unexpected bug'), null];
    }

    #[DataProvider('failures')]
    public function test_each_failure_is_classified(Throwable $failure, ?DispatchFailureReason $expectedReason): void
    {
        $this->assertSame($expectedReason, (new DispatchFailureClassifier)->classify($failure));
    }

    public function test_only_the_mail_service_unavailability_is_temporary(): void
    {
        $this->assertFalse(DispatchFailureReason::MailServiceUnavailable->isPermanent());
        $this->assertTrue(DispatchFailureReason::InvalidAddress->isPermanent());
        $this->assertTrue(DispatchFailureReason::RecipientRejected->isPermanent());
    }
}
