<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Booking\Enums\ReservationStatus;
use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Livewire\CategoryViews;
use Functional\Inspection\Livewire\Comparison;
use Functional\Inspection\Livewire\PhoneCapture;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionScreensFeedbackTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->actingAs($this->seededEmployee());
    }

    public function test_a_reported_damage_is_confirmed(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $this->photographEveryView($reservation, InspectionStep::Return);
        $reservation->update(['status' => ReservationStatus::Closed]);

        Livewire::test(Comparison::class, ['reservation' => $reservation])
            ->set('reservationViewId', $this->firstView($reservation)->id)
            ->set('comment', 'Rayure sur le capot')
            ->call('report')
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Dégât signalé.');
    }

    public function test_every_change_of_the_category_views_is_confirmed(): void
    {
        $category = MachineCategory::factory()->create();

        Livewire::test(CategoryViews::class, ['category' => $category])
            ->set('newLabel', 'Godet')
            ->call('add')
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Liste des vues enregistrée.')
            ->call('resetToDefault')
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Liste des vues enregistrée.');
    }

    public function test_the_qr_code_has_a_text_alternative(): void
    {
        Livewire::test(PhotosPanel::class, ['reservation' => $this->reservationStartingToday()])
            ->call('generate')
            ->assertSeeHtml('role="img"')
            ->assertSeeHtml('aria-label="QR code de prise de photos de départ, à scanner avec votre téléphone"');
    }

    public function test_the_phone_takes_photos_with_a_real_button(): void
    {
        $token = $this->openSession($this->reservationStartingToday());

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->assertDontSeeHtml('class="sr-only"')
            ->assertSeeHtml('type="button"')
            ->assertSee('Prendre une photo');
    }
}
