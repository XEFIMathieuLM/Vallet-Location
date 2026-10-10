<?php

namespace Functional\Portal\Tests\Feature\Customer;

use Carbon\CarbonImmutable;
use Functional\Booking\Enums\ReservationStatus;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Certification\Enums\CertificateStatus;
use Functional\Certification\Enums\DispatchChannel;
use Functional\Certification\Models\CertificateDispatch;
use Functional\Certification\Models\ReservationCertificate;
use Functional\Certification\Models\VgpReport;
use Functional\Fleet\Models\Machine;
use Functional\Portal\Livewire\Customer\MyReservations;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCertificateDownloadTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        Storage::fake('vgp-reports');
        $this->customer = Customer::factory()->create();
        $this->actingAs($this->attachedCustomerAccount($this->customer), 'customer');
    }

    public function test_the_customer_downloads_the_report_of_the_last_successful_dispatch(): void
    {
        $reservation = $this->vgpReservation();
        $certificate = ReservationCertificate::factory()->for($reservation)->withStatus(CertificateStatus::Sent)->create();
        $firstReport = VgpReport::factory()->for($reservation->machine)->create(['original_name' => 'premier.pdf']);
        $lastReport = VgpReport::factory()->for($reservation->machine)->create(['original_name' => 'dernier.pdf']);
        CertificateDispatch::factory()->for($certificate, 'certificate')->for($firstReport, 'report')->create(['attempted_at' => now()->subDays(2)]);
        CertificateDispatch::factory()->for($certificate, 'certificate')->for($lastReport, 'report')->create(['attempted_at' => now()->subDay(), 'is_automatic' => false, 'author_id' => $this->userWithoutPermission()->getKey()]);
        CertificateDispatch::factory()->for($certificate, 'certificate')->for($firstReport, 'report')->failed()->create(['attempted_at' => now(), 'is_automatic' => false, 'author_id' => $this->userWithoutPermission()->getKey()]);

        Livewire::test(MyReservations::class)->assertSee(__('portal::reservations.download_certificate'))->assertSee(route('portal.reservations.certificate', $reservation));

        $response = $this->get(route('portal.reservations.certificate', $reservation));
        $response->assertOk()->assertDownload();
        $this->assertSame((string) Storage::disk('vgp-reports')->get($lastReport->file_path), $response->streamedContent());
    }

    public function test_a_hand_delivered_report_is_downloadable(): void
    {
        $reservation = $this->vgpReservation();
        $certificate = ReservationCertificate::factory()->for($reservation)->withStatus(CertificateStatus::HandDelivered)->create();
        $report = VgpReport::factory()->for($reservation->machine)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->for($report, 'report')->create(['channel' => DispatchChannel::Hand, 'recipient_email' => null, 'is_automatic' => false, 'author_id' => $this->userWithoutPermission()->getKey()]);

        $this->get(route('portal.reservations.certificate', $reservation))->assertOk()->assertDownload();
    }

    public function test_a_certificate_not_yet_sent_is_announced_as_not_available(): void
    {
        $reservation = $this->vgpReservation();
        $certificate = ReservationCertificate::factory()->for($reservation)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->failed()->create();

        Livewire::test(MyReservations::class)->assertSee(__('portal::reservations.certificate_not_available'));
        $this->get(route('portal.reservations.certificate', $reservation))->assertNotFound();
    }

    public function test_a_machine_not_subject_to_vgp_offers_no_certificate(): void
    {
        Reservation::factory()->for($this->customer)->for(Machine::factory()->create(['is_subject_to_vgp' => false]))->create();

        Livewire::test(MyReservations::class)
            ->assertDontSee(__('portal::reservations.download_certificate'))
            ->assertDontSee(__('portal::reservations.certificate_not_available'));
    }

    public function test_a_cancelled_reservation_offers_no_certificate(): void
    {
        $reservation = $this->vgpReservation(ReservationStatus::Cancelled);
        $certificate = ReservationCertificate::factory()->for($reservation)->withStatus(CertificateStatus::Sent)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->for(VgpReport::factory()->for($reservation->machine), 'report')->create();

        Livewire::test(MyReservations::class)->assertDontSee(__('portal::reservations.download_certificate'));
        $this->get(route('portal.reservations.certificate', $reservation))->assertNotFound();
    }

    public function test_the_certificate_of_another_customer_is_out_of_reach(): void
    {
        $otherReservation = Reservation::factory()->for(Machine::factory()->vgpValid())->create();
        $certificate = ReservationCertificate::factory()->for($otherReservation)->withStatus(CertificateStatus::Sent)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->for(VgpReport::factory()->for($otherReservation->machine), 'report')->create();

        $this->get(route('portal.reservations.certificate', $otherReservation))->assertNotFound();
    }

    public function test_downloading_changes_nothing_in_the_certificate(): void
    {
        $reservation = $this->vgpReservation();
        $certificate = ReservationCertificate::factory()->for($reservation)->withStatus(CertificateStatus::Sent)->create();
        CertificateDispatch::factory()->for($certificate, 'certificate')->for(VgpReport::factory()->for($reservation->machine), 'report')->create();
        $before = $certificate->fresh()?->toArray();

        $this->get(route('portal.reservations.certificate', $reservation))->assertOk();

        $this->assertSame($before, $certificate->fresh()?->toArray());
        $this->assertSame(1, CertificateDispatch::query()->count());
    }

    private function vgpReservation(ReservationStatus $status = ReservationStatus::Confirmed): Reservation
    {
        return Reservation::factory()->for($this->customer)->for(Machine::factory()->vgpValid())->withStatus($status)->create();
    }
}
