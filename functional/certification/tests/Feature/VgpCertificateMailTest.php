<?php

namespace Functional\Certification\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Certification\Notifications\VgpCertificateNotification;
use Functional\Fleet\Models\Agency;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Models\MachineCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VgpCertificateMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_email_presents_the_reservation_and_attaches_the_deposited_report(): void
    {
        Storage::fake('vgp-reports');
        $machine = Machine::factory()
            ->for(MachineCategory::factory()->requiringVgp()->create(['name' => 'Nacelle articulée']), 'category')
            ->for(Agency::factory()->create(['name' => 'Agence de Rouen']))
            ->vgpValid()
            ->create(['reference' => 'NAC-0042']);
        $report = VgpReport::factory()->for($machine)->create([
            'original_name' => 'rapport-apave.pdf',
            'verified_on' => CarbonImmutable::parse('2026-10-02'),
            'due_on' => CarbonImmutable::parse('2027-04-01'),
        ]);
        $reservation = Reservation::factory()->for($machine)->for(Customer::factory()->create(['name' => 'BTP Normandie', 'email' => 'chantier@exemple.fr']))
            ->between(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))->create();
        $certificate = ReservationCertificate::factory()->for($reservation)->create();

        $mailable = (new VgpCertificateNotification($certificate, $report))->toMail(Notification::route('mail', 'chantier@exemple.fr'));

        $this->assertInstanceOf(Mailable::class, $mailable);
        $mailable->assertTo('chantier@exemple.fr');
        $mailable->assertHasSubject('Attestation VGP – NAC-0042 – réservation du 10/11/2026 au 14/11/2026');
        $mailable->assertSeeInHtml('BTP Normandie');
        $mailable->assertSeeInHtml('Nacelle articulée');
        $mailable->assertSeeInHtml('Agence de Rouen');
        $mailable->assertSeeInHtml('02/10/2026');
        $mailable->assertSeeInHtml('01/04/2027');
        $mailable->assertHasAttachedData((string) Storage::disk('vgp-reports')->get($report->file_path), 'VGP-NAC-0042-2026-10-02.pdf', ['mime' => 'application/pdf']);
    }
}
