<?php

namespace Functional\Certification\Tests\Unit;

use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Exceptions\IllegalCertificateTransitionException;
use Functional\Certification\States\CertificateStateFactory;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CertificateStateTest extends TestCase
{
    use AssertsRefusals;

    private const TRANSITIONS = ['awaitEmail', 'queue', 'send', 'fail', 'handDeliver'];

    private const LEGAL_TRANSITIONS = [
        'awaiting_report' => ['awaitEmail' => 'awaiting_email', 'queue' => 'pending'],
        'awaiting_email' => ['queue' => 'pending', 'handDeliver' => 'hand_delivered'],
        'pending' => ['send' => 'sent', 'fail' => 'failed', 'handDeliver' => 'hand_delivered'],
        'failed' => ['queue' => 'pending', 'send' => 'sent', 'handDeliver' => 'hand_delivered'],
        'sent' => [],
        'hand_delivered' => [],
    ];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function everyStatusAndTransition(): iterable
    {
        foreach (array_keys(self::LEGAL_TRANSITIONS) as $status) {
            foreach (self::TRANSITIONS as $transition) {
                yield "{$status} → {$transition}" => [$status, $transition];
            }
        }
    }

    #[DataProvider('everyStatusAndTransition')]
    public function test_each_transition_is_allowed_only_from_its_legal_statuses(string $status, string $transition): void
    {
        $state = CertificateStateFactory::fromStatus(CertificateStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->assertRefused(IllegalCertificateTransitionException::class, 'Impossible de', fn () => $state->{$transition}());

            return;
        }

        $this->assertSame($expectedStatus, $state->{$transition}()->status()->value);
    }

    public function test_only_sent_and_hand_delivered_certificates_are_delivered(): void
    {
        $deliveredStatuses = array_values(array_filter(CertificateStatus::cases(), fn (CertificateStatus $status): bool => $status->isDelivered()));

        $this->assertSame([CertificateStatus::Sent, CertificateStatus::HandDelivered], $deliveredStatuses);
    }
}
