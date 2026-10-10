<?php

namespace Functional\Certification\Tests\Concerns;

use Functional\Certification\Tests\Doubles\SimulatedMailTransport;
use Illuminate\Support\Facades\Mail;

trait SimulatesMailTransport
{
    protected function simulateMailTransport(string $mode = SimulatedMailTransport::ACCEPTS): void
    {
        SimulatedMailTransport::$mode = $mode;
        SimulatedMailTransport::$sentMessages = [];
        Mail::extend('simulated', fn (): SimulatedMailTransport => new SimulatedMailTransport);
        config(['mail.mailers.simulated' => ['transport' => 'simulated'], 'mail.default' => 'simulated']);
        Mail::purge('simulated');
    }

    protected function switchMailTransportTo(string $mode): void
    {
        SimulatedMailTransport::$mode = $mode;
    }

    protected function sentMailCount(): int
    {
        return count(SimulatedMailTransport::$sentMessages);
    }
}
