<?php

namespace Functional\Inspection\Tests\Fixtures;

use Functional\Inspection\Models\Damage;
use Livewire\Component;

class FakeDamageAction extends Component
{
    public Damage $damage;

    public function render(): string
    {
        return '<span>Transmettre à la facturation #{{ $damage->id }}</span>';
    }
}
