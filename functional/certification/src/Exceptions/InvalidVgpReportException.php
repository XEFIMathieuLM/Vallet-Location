<?php

namespace Functional\Certification\Exceptions;

use Functional\Fleet\Exceptions\RefusalException;
use Functional\Fleet\Models\Machine;

final class InvalidVgpReportException extends RefusalException
{
    public static function notSubjectToVgp(Machine $machine): self
    {
        return new self("Machine {$machine->reference} is not subject to VGP.", 'certification::reports.refusals.not_subject_to_vgp', ['reference' => $machine->reference]);
    }

    public static function unacceptedFormat(string $extension): self
    {
        return new self("VGP report format [{$extension}] is not accepted.", 'certification::reports.refusals.unaccepted_format', [
            'formats' => implode(', ', config()->array('certification.accepted_mimes')),
        ]);
    }

    public static function tooLarge(int $sizeKilobytes): self
    {
        return new self("VGP report of {$sizeKilobytes} KB exceeds the maximum size.", 'certification::reports.refusals.too_large', [
            'megabytes' => intdiv(config()->integer('certification.max_report_kilobytes'), 1024),
        ]);
    }

    public static function dueDateNotAfterVerification(): self
    {
        return new self('The VGP due date is not after the verification date.', 'certification::reports.refusals.due_not_after_verification');
    }
}
