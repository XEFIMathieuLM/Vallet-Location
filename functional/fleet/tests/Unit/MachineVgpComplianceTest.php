<?php

namespace Functional\Fleet\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Fleet\Vgp\VgpCompliance;
use PHPUnit\Framework\TestCase;

class MachineVgpComplianceTest extends TestCase
{
    public function test_a_machine_not_subject_to_vgp_is_always_compliant(): void
    {
        $compliance = new VgpCompliance(isSubjectToVgp: false, vgpDueDate: null);

        $this->assertTrue($compliance->coversUntil(CarbonImmutable::parse('2030-01-01')));
    }

    public function test_a_machine_subject_to_vgp_without_due_date_is_not_compliant(): void
    {
        $compliance = new VgpCompliance(isSubjectToVgp: true, vgpDueDate: null);

        $this->assertFalse($compliance->coversUntil(CarbonImmutable::parse('2026-11-14')));
    }

    public function test_the_vgp_must_cover_the_end_date_inclusive(): void
    {
        $compliance = new VgpCompliance(isSubjectToVgp: true, vgpDueDate: CarbonImmutable::parse('2026-11-14'));

        $this->assertTrue($compliance->coversUntil(CarbonImmutable::parse('2026-11-14')));
        $this->assertFalse($compliance->coversUntil(CarbonImmutable::parse('2026-11-15')));
    }
}
