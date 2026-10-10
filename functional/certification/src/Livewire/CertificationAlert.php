<?php

namespace Functional\Certification\Livewire;

use Functional\Certification\Queries\CertificatesToHandle;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificationAlert extends Component
{
    public const NAME = 'certification.alert';

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return ['echo-private:fleet,.certificate.changed' => '$refresh'];
    }

    public function render(CertificatesToHandle $certificatesToHandle): View
    {
        return view('certification::livewire.certification-alert', [
            'certificatesCount' => $certificatesToHandle->query()->count(),
        ]);
    }
}
