<?php

namespace Functional\Portal\Tests\Feature;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Exceptions\InvalidReservationDatesException;
use Functional\Booking\Models\Customer;
use Functional\Booking\Models\Reservation;
use Functional\Booking\Queries\AvailableMachinesQuery;
use Functional\Fleet\Enums\MachineStatus;
use Functional\Fleet\Models\Machine;
use Functional\Fleet\Tests\Concerns\AssertsRefusals;
use Functional\Portal\Actions\SendReservationRequest;
use Functional\Portal\Enums\PortalHistoryEvent;
use Functional\Portal\Enums\ReservationRequestStatus;
use Functional\Portal\Events\ReservationRequestChanged;
use Functional\Portal\Exceptions\DuplicatePendingRequestException;
use Functional\Portal\Exceptions\PendingRequestLimitReachedException;
use Functional\Portal\Exceptions\RequestedMachineUnavailableException;
use Functional\Portal\Livewire\Customer\SendRequestForm;
use Functional\Portal\Models\CategoryIndicativePrice;
use Functional\Portal\Models\CustomerAccount;
use Functional\Portal\Models\ReservationRequest;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SendReservationRequestTest extends TestCase
{
    use AssertsRefusals, BuildsPortalFixtures, RefreshDatabase;

    private CustomerAccount $account;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00', 'Europe/Paris'));
        $this->account = $this->customerAccount();
        $this->machine = $this->reservableMachine();
        $this->actingAs($this->account, 'customer');
    }

    public function test_a_customer_sends_a_pending_request_with_a_comment_and_the_indicative_price_of_the_moment(): void
    {
        Event::fake([ReservationRequestChanged::class]);
        CategoryIndicativePrice::factory()->for($this->machine->category, 'category')->create(['daily_price_cents' => 9500]);

        Livewire::test(SendRequestForm::class, ['machineId' => $this->machine->id, 'startDate' => '2026-11-10', 'endDate' => '2026-11-14'])
            ->assertSee($this->machine->reference)
            ->assertSee(__('portal::search.request_notice'))
            ->set('comment', 'Chantier au 12 rue des Lilas, Rouen')
            ->call('send')
            ->assertHasNoErrors()
            ->assertRedirect(route('portal.requests'));

        $reservationRequest = ReservationRequest::query()->sole();
        $this->assertSame(ReservationRequestStatus::Pending, $reservationRequest->status);
        $this->assertTrue($reservationRequest->account->is($this->account));
        $this->assertSame('2026-11-10', $reservationRequest->start_date->toDateString());
        $this->assertSame('2026-11-14', $reservationRequest->end_date->toDateString());
        $this->assertSame('Chantier au 12 rue des Lilas, Rouen', $reservationRequest->comment);
        $this->assertSame(9500, $reservationRequest->indicative_daily_price_cents);
        $this->assertTrue(Activity::query()->where('event', PortalHistoryEvent::RequestSent->value)->whereMorphedTo('subject', $reservationRequest)->exists());
        Event::assertDispatched(ReservationRequestChanged::class);
    }

    public function test_a_pending_request_neither_blocks_the_machine_nor_hides_it_from_the_search(): void
    {
        Auth::shouldUse('web');
        $this->seedPermissions();
        $this->send('2026-11-10', '2026-11-14');

        $this->assertTrue(app(AvailableMachinesQuery::class)->get(CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'))->contains($this->machine));

        $reservation = app(CreateReservation::class)->handle($this->employee(), $this->machine, Customer::factory()->create(), CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'));

        $this->assertTrue($reservation->exists);
        $this->assertSame(ReservationRequestStatus::Pending, ReservationRequest::query()->sole()->status);
    }

    public function test_a_machine_that_became_unavailable_is_refused_at_sending(): void
    {
        Reservation::factory()->for($this->machine)->between(CarbonImmutable::parse('2026-11-13'), CarbonImmutable::parse('2026-11-15'))->create();

        $this->assertRefused(RequestedMachineUnavailableException::class, 'plus disponible', fn () => $this->send('2026-11-10', '2026-11-14'));

        $this->machine->update(['status' => MachineStatus::OutOfOrder]);
        $this->assertRefused(RequestedMachineUnavailableException::class, 'plus disponible', fn () => $this->send('2026-11-20', '2026-11-21'));
        $this->assertSame(0, ReservationRequest::query()->count());
    }

    public function test_inconsistent_dates_are_refused(): void
    {
        $this->assertRefused(InvalidReservationDatesException::class, 'passé', fn () => $this->send('2026-10-09', '2026-10-12'));
        $this->assertRefused(InvalidReservationDatesException::class, 'fin', fn () => $this->send('2026-11-14', '2026-11-10'));
        $this->assertSame(0, ReservationRequest::query()->count());
    }

    public function test_a_request_overlapping_a_pending_request_of_the_same_account_on_the_same_machine_is_refused(): void
    {
        $this->send('2026-11-10', '2026-11-14');

        $this->assertRefused(DuplicatePendingRequestException::class, 'déjà une demande en attente', fn () => $this->send('2026-11-14', '2026-11-16'));
        $this->assertSame(1, ReservationRequest::query()->count());
    }

    public function test_an_eleventh_pending_request_is_refused(): void
    {
        ReservationRequest::factory()->count(10)->for($this->account, 'account')->create();

        $this->assertRefused(PendingRequestLimitReachedException::class, '10 demandes en attente', fn () => $this->send('2026-11-10', '2026-11-14'));
        $this->assertSame(10, ReservationRequest::query()->count());
    }

    public function test_a_comment_longer_than_500_characters_is_refused(): void
    {
        Livewire::test(SendRequestForm::class, ['machineId' => $this->machine->id, 'startDate' => '2026-11-10', 'endDate' => '2026-11-14'])
            ->set('comment', Str::repeat('a', 501))
            ->call('send')
            ->assertHasErrors(['comment' => 'max']);

        $this->assertSame(0, ReservationRequest::query()->count());
    }

    public function test_the_form_displays_a_refusal_instead_of_failing(): void
    {
        $this->machine->update(['status' => MachineStatus::Workshop]);

        Livewire::test(SendRequestForm::class, ['machineId' => $this->machine->id, 'startDate' => '2026-11-10', 'endDate' => '2026-11-14'])
            ->call('send')
            ->assertHasErrors('refusal');
    }

    private function send(string $startDate, string $endDate): ReservationRequest
    {
        return app(SendReservationRequest::class)->handle($this->account, $this->machine, CarbonImmutable::parse($startDate), CarbonImmutable::parse($endDate), null);
    }
}
