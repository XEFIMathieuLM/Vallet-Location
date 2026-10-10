<?php

namespace Functional\Inspection\Tests\Feature;

use Functional\Fleet\Models\MachineCategory;
use Functional\Inspection\Enums\InspectionStep;
use Functional\Inspection\Livewire\CategoryViews;
use Functional\Inspection\Livewire\DamagesList;
use Functional\Inspection\Livewire\PhoneCapture;
use Functional\Inspection\Livewire\PhotosPanel;
use Functional\Inspection\Models\CategoryView;
use Functional\Inspection\Models\Photo;
use Functional\Inspection\Tests\Concerns\BuildsPhotoSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionConfirmationsTest extends TestCase
{
    use BuildsPhotoSessions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPhotoStorage();
        $this->actingAs($this->seededEmployee());
    }

    public function test_deleting_a_photo_from_the_desk_asks_for_a_confirmation_in_a_modal(): void
    {
        $reservation = $this->reservationStartingToday();
        $this->photographEveryView($reservation, InspectionStep::Departure);
        $photo = Photo::query()->firstOrFail();

        Livewire::test(PhotosPanel::class, ['reservation' => $reservation])
            ->assertDontSeeHtml('wire:confirm')
            ->call('confirmPhotoDeletion', $photo->id)
            ->assertDispatched('modal-show', name: 'delete-photo')
            ->call('deletePhoto', $photo->id)
            ->assertDispatched('modal-close', name: 'delete-photo');

        $this->assertModelMissing($photo);
    }

    public function test_deleting_a_photo_from_the_phone_asks_for_a_confirmation_in_a_modal(): void
    {
        $reservation = $this->reservationStartingToday();
        $token = $this->openSession($reservation);
        $this->photographEveryView($reservation, InspectionStep::Departure);

        Livewire::test(PhoneCapture::class, ['token' => $token])
            ->assertDontSeeHtml('wire:confirm')
            ->call('confirmPhotoDeletion', Photo::query()->firstOrFail()->id)
            ->assertDispatched('modal-show', name: 'delete-photo');
    }

    public function test_going_back_to_the_default_views_and_resolving_a_damage_use_modals(): void
    {
        $category = MachineCategory::factory()->create();
        CategoryView::factory()->for($category, 'category')->create(['position' => 1]);

        Livewire::test(CategoryViews::class, ['category' => $category])
            ->assertDontSeeHtml('wire:confirm')
            ->assertSeeHtml('data-flux-modal');
        Livewire::test(DamagesList::class)
            ->assertDontSeeHtml('wire:confirm')
            ->assertSee('Aucun dégât à traiter');
    }
}
