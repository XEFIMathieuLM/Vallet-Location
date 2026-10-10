<?php

namespace Functional\Billing\States;

use Functional\Billing\Exceptions\IllegalTransmissionTransitionException;

trait RefusesTransmissionTransitions
{
    public function send(): TransmissionState
    {
        throw IllegalTransmissionTransitionException::for($this, 'send');
    }

    public function fail(): TransmissionState
    {
        throw IllegalTransmissionTransitionException::for($this, 'fail');
    }

    public function requeue(): TransmissionState
    {
        throw IllegalTransmissionTransitionException::for($this, 'requeue');
    }

    public function export(): TransmissionState
    {
        throw IllegalTransmissionTransitionException::for($this, 'export');
    }

    public function canBeSent(): bool
    {
        return false;
    }

    public function canBeExported(): bool
    {
        return false;
    }

    public function canBeRetried(): bool
    {
        return false;
    }
}
