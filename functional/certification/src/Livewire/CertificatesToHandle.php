<?php

namespace Functional\Certification\Livewire;

use Functional\Certification\Queries\CertificatesToHandle as CertificatesToHandleQuery;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificatesToHandle extends Component
{
    public function render(CertificatesToHandleQuery $certificatesToHandle): View
    {
        return view('certification::livewire.certificates-to-handle', [
            'certificates' => $certificatesToHandle->query()->with(['reservation.machine', 'reservation.customer', 'lastDispatch'])->get(),
        ])->title(__('certification::certificates.to_handle.title'));
    }
}
