<?php

namespace Functional\Certification\Tests\Feature;

use Functional\Booking\Enums\ReservationTransition;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Livewire\ReservationCertificateSection;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Tests\Concerns\BuildsCertificateScenarios;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationCertificateSectionTest extends TestCase
{
    use BuildsCertificateScenarios, CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCertificationScenario();
    }

    /**
     * @return iterable<string, array{CertificateStatus, string}>
     */
    public static function displayedStatuses(): iterable
    {
        yield 'en attente de rapport' => [CertificateStatus::AwaitingReport, 'rapport de VGP non déposé'];
        yield 'sans e-mail' => [CertificateStatus::AwaitingEmail, 'e-mail du client manquant'];
        yield 'en attente d\'envoi' => [CertificateStatus::Pending, 'En attente d\'envoi'];
        yield 'en échec' => [CertificateStatus::Failed, 'Échec de l\'envoi'];
        yield 'envoyée' => [CertificateStatus::Sent, 'Envoyée'];
        yield 'remise' => [CertificateStatus::HandDelivered, 'Remise en main propre'];
    }

    #[DataProvider('displayedStatuses')]
    public function test_the_section_shows_the_certificate_status(CertificateStatus $status, string $expectedText): void
    {
        $reservation = Reservation::factory()->for(Machine::factory()->vgpValid())->create();
        ReservationCertificate::factory()->for($reservation)->withStatus($status)->create();

        Livewire::test(ReservationCertificateSection::class, ['reservation' => $reservation])
            ->assertSee('Attestation VGP')
            ->assertSee($expectedText);
    }

    public function test_the_section_is_empty_for_a_machine_not_subject_to_vgp(): void
    {
        $reservation = Reservation::factory()->for(Machine::factory()->create(['is_subject_to_vgp' => false]))->create();

        Livewire::test(ReservationCertificateSection::class, ['reservation' => $reservation])
            ->assertDontSee('Attestation VGP');
    }

    public function test_the_section_announces_departure_readiness_when_mounted(): void
    {
        $notConcerned = Reservation::factory()->for(Machine::factory()->create(['is_subject_to_vgp' => false]))->create();
        $pending = Reservation::factory()->for(Machine::factory()->vgpValid())->create();
        ReservationCertificate::factory()->for($pending)->create();

        Livewire::test(ReservationCertificateSection::class, ['reservation' => $notConcerned])
            ->assertDispatched('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: ReservationCertificateSection::NAME, is_ready: true);
        Livewire::test(ReservationCertificateSection::class, ['reservation' => $pending])
            ->assertDispatched('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: ReservationCertificateSection::NAME, is_ready: false);
    }
}
