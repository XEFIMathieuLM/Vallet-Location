<?php

namespace App\Livewire\Users;

use App\Actions\Users\ChangeUserActivation;
use App\Actions\Users\CreateEmployee;
use App\Models\User;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, User> $users
 */
class UserIndex extends Component
{
    use DisplaysRefusals;

    public string $name = '';

    public string $email = '';

    public ?int $agencyId = null;

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->with('agency')->orderBy('name')->get();
    }

    public function create(CreateEmployee $createEmployee): void
    {
        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'agencyId' => ['required', 'exists:agencies,id'],
            ],
            attributes: [
                'name' => __('users.fields.name'),
                'email' => __('users.fields.email'),
                'agencyId' => __('users.fields.agency'),
            ],
        );

        $employee = $createEmployee->handle($validated['name'], $validated['email'], (int) $validated['agencyId']);

        $this->reset('name', 'email', 'agencyId');
        session()->flash('user-saved', __('users.created', ['name' => $employee->name]));
    }

    public function changeAgency(int $userId, int $agencyId): void
    {
        User::query()->findOrFail($userId)->update(['agency_id' => Agency::query()->findOrFail($agencyId)->id]);
    }

    public function deactivate(int $userId, ChangeUserActivation $changeUserActivation): void
    {
        /** @var User $author */
        $author = Auth::user();

        $changeUserActivation->deactivate(User::query()->findOrFail($userId), $author);
    }

    public function reactivate(int $userId, ChangeUserActivation $changeUserActivation): void
    {
        $changeUserActivation->reactivate(User::query()->findOrFail($userId));
    }

    public function render(): View
    {
        return view('livewire.users.user-index', [
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('users.title'));
    }
}
