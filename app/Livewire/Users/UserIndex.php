<?php

namespace App\Livewire\Users;

use App\Actions\Users\ChangeUserActivation;
use App\Actions\Users\CreateEmployee;
use App\Models\User;
use Flux\Flux;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Agency;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Collection<int, User> $users
 * @property-read User|null $userToDeactivate
 */
class UserIndex extends Component
{
    use DisplaysRefusals;

    public string $name = '';

    public string $email = '';

    public ?int $agencyId = null;

    #[Locked]
    public ?int $userToDeactivateId = null;

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

    #[Computed]
    public function userToDeactivate(): ?User
    {
        return $this->userToDeactivateId === null ? null : User::query()->find($this->userToDeactivateId);
    }

    public function changeAgency(int $userId, int $agencyId): void
    {
        $user = User::query()->findOrFail($userId);
        $agency = Agency::query()->findOrFail($agencyId);

        $user->agency()->associate($agency)->save();
        Flux::toast(text: __('users.agency_changed', ['name' => $user->name, 'agency' => $agency->name]), variant: 'success');
    }

    public function confirmDeactivation(int $userId): void
    {
        $this->userToDeactivateId = $userId;
        Flux::modal('deactivate-user')->show();
    }

    public function deactivate(int $userId, ChangeUserActivation $changeUserActivation): void
    {
        /** @var User $author */
        $author = Auth::user();

        $user = $changeUserActivation->deactivate(User::query()->findOrFail($userId), $author);

        Flux::modal('deactivate-user')->close();
        $this->userToDeactivateId = null;
        Flux::toast(text: __('users.deactivated_toast', ['name' => $user->name]), variant: 'success');
    }

    public function reactivate(int $userId, ChangeUserActivation $changeUserActivation): void
    {
        $user = $changeUserActivation->reactivate(User::query()->findOrFail($userId));

        Flux::toast(text: __('users.reactivated_toast', ['name' => $user->name]), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.users.user-index', [
            'agencies' => Agency::query()->orderBy('name')->get(),
        ])->title(__('users.title'));
    }
}
