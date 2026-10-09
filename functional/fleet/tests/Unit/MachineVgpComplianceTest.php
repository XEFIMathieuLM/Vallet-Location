<?php

namespace Functional\Fleet\Tests\Unit;

use Carbon\CarbonImmutable;
use Functional\Fleet\Models\Machine;
use Tests\TestCase;

class MachineVgpComplianceTest extends TestCase
{
    public function test_a_machine_not_subject_to_vgp_is_always_compliant(): void
    {
        $machine = new Machine(['is_subject_to_vgp' => false, 'vgp_due_date' => null]);

        $this->assertTrue($machine->isVgpCompliantUntil(CarbonImmutable::parse('2030-01-01')));
    }

    public function test_a_machine_subject_to_vgp_without_due_date_is_not_compliant(): void
    {
        $machine = new Machine(['is_subject_to_vgp' => true, 'vgp_due_date' => null]);

        $this->assertFalse($machine->isVgpCompliantUntil(CarbonImmutable::parse('2026-11-14')));
    }

    public function test_the_vgp_must_cover_the_end_date_inclusive(): void
    {
        $machine = new Machine(['is_subject_to_vgp' => true, 'vgp_due_date' => '2026-11-14']);

        $this->assertTrue($machine->isVgpCompliantUntil(CarbonImmutable::parse('2026-11-14')));
        $this->assertFalse($machine->isVgpCompliantUntil(CarbonImmutable::parse('2026-11-15')));
    }
}
