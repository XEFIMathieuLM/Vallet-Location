<?php

namespace Functional\Certification\Jobs;

use Functional\Certification\Actions\SendCertificate;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendCertificateJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $certificateId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->certificateId;
    }

    public function handle(SendCertificate $sendCertificate): void
    {
        $sendCertificate->handle($this->certificateId);
    }
}
