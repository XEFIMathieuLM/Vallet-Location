<?php

namespace Functional\Billing\States;

use Functional\Billing\Enums\TransmissionStatus;

interface TransmissionState
{
    public function status(): TransmissionStatus;

    public function send(): TransmissionState;

    public function fail(): TransmissionState;

    public function requeue(): TransmissionState;

    public function export(): TransmissionState;

    public function canBeSent(): bool;

    public function canBeExported(): bool;

    public function canBeRetried(): bool;
}
