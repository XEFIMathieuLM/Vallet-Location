<?php

namespace Functional\Billing\Tests\Unit;

use Functional\Billing\Enums\TransmissionStatus;
use Functional\Billing\Exceptions\IllegalTransmissionTransitionException;
use Functional\Billing\States\TransmissionStateFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransmissionStateTest extends TestCase
{
    private const TRANSITIONS = ['send', 'fail', 'requeue', 'export'];

    private const LEGAL_TRANSITIONS = [
        'pending' => ['send' => 'sent', 'fail' => 'failed', 'export' => 'exported'],
        'failed' => ['requeue' => 'pending', 'export' => 'exported'],
        'sent' => [],
        'exported' => [],
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
        $state = TransmissionStateFactory::fromStatus(TransmissionStatus::from($status));
        $expectedStatus = self::LEGAL_TRANSITIONS[$status][$transition] ?? null;

        if ($expectedStatus === null) {
            $this->expectException(IllegalTransmissionTransitionException::class);
        }

        $this->assertSame($expectedStatus, $state->{$transition}()->status()->value);
    }

    public function test_only_a_pending_transmission_can_be_sent(): void
    {
        $sendableStatuses = array_filter(
            TransmissionStatus::cases(),
            fn (TransmissionStatus $status): bool => TransmissionStateFactory::fromStatus($status)->canBeSent(),
        );

        $this->assertSame([TransmissionStatus::Pending], array_values($sendableStatuses));
    }

    public function test_pending_and_failed_transmissions_can_be_exported(): void
    {
        $exportableStatuses = array_filter(
            TransmissionStatus::cases(),
            fn (TransmissionStatus $status): bool => TransmissionStateFactory::fromStatus($status)->canBeExported(),
        );

        $this->assertSame([TransmissionStatus::Pending, TransmissionStatus::Failed], array_values($exportableStatuses));
    }

    public function test_only_a_failed_transmission_can_be_retried(): void
    {
        $retriableStatuses = array_filter(
            TransmissionStatus::cases(),
            fn (TransmissionStatus $status): bool => TransmissionStateFactory::fromStatus($status)->canBeRetried(),
        );

        $this->assertSame([TransmissionStatus::Failed], array_values($retriableStatuses));
    }
}
