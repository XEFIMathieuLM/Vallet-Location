<?php

namespace Functional\Booking\Livewire;

use Carbon\CarbonImmutable;
use Functional\Booking\Actions\CreateReservation;
use Functional\Booking\Data\NewCustomer;
use Functional\Booking\Enums\CustomerType;
use Functional\Booking\Extensions\CustomerBadges;
use Functional\Booking\Models\Customer;
use Functional\Booking\ValueObjects\CustomerBadge;
use Functional\Fleet\Contracts\AgencyMember;
use Functional\Fleet\Livewire\Concerns\DisplaysRefusals;
use Functional\Fleet\Models\Machine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Machine $machine
 * @property-read Collection<int, Customer> $customers
 * @property-read array<int, list<CustomerBadge>> $customerBadges
 */
class CreateReservationForm extends Component
{
    use DisplaysRefusals;

    private const CUSTOMER_SEARCH_LIMIT = 20;

    #[Locked]
    #[Url(as: 'machine')]
    public int $machineId = 0;

    #[Url(as: 'du')]
    public string $startDate = '';

    #[Url(as: 'au')]
    public string $endDate = '';

    public string $customerSearch = '';

    public ?int $customerId = null;

    public bool $isNewCustomer = false;

    public string $newCustomerName = '';

    public string $newCustomerPhone = '';

    public string $newCustomerEmail = '';

    public string $newCustomerType = '';

    #[Computed]
    public function machine(): Machine
    {
        return Machine::query()->with(['category', 'agency'])->findOrFail($this->machineId);
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()
            ->when($this->customerSearch !== '', fn ($query) => $query->whereLike('name', "%{$this->customerSearch}%"))
            ->orderBy('name')
            ->limit(self::CUSTOMER_SEARCH_LIMIT)
            ->get();
    }

    /**
     * @return array<int, list<CustomerBadge>>
     */
    #[Computed]
    public function customerBadges(): array
    {
        $customerIds = $this->customers->modelKeys();

        if ($this->customerId !== null) {
            $customerIds[] = $this->customerId;
        }

        return app(CustomerBadges::class)->forCustomers(array_values(array_unique($customerIds)));
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return array_fill_keys(app(CustomerBadges::class)->refreshListeners(), '$refresh');
    }

    public function save(CreateReservation $createReservation): void
    {
        $this->validate();

        $author = Auth::user();

        if (! $author instanceof AgencyMember) {
            throw new AuthorizationException;
        }

        $reservation = $createReservation->handle(
            $author,
            $this->machine,
            $this->selectedCustomer(),
            CarbonImmutable::parse($this->startDate),
            CarbonImmutable::parse($this->endDate),
        );

        session()->flash('reservation-created', __('booking::reservations.form.created', [
            'reference' => $this->machine->reference,
            'start' => $reservation->start_date->format('d/m/Y'),
            'end' => $reservation->end_date->format('d/m/Y'),
        ]));

        $this->redirectRoute('reservations.show', $reservation, navigate: true);
    }

    /**
     * @return array<string, array<int, string|Enum>>
     */
    protected function rules(): array
    {
        $dateRules = ['required', 'date_format:Y-m-d'];

        if (! $this->isNewCustomer) {
            return ['startDate' => $dateRules, 'endDate' => $dateRules, 'customerId' => ['required', 'exists:customers,id']];
        }

        return [
            'startDate' => $dateRules,
            'endDate' => $dateRules,
            'newCustomerName' => ['required', 'string', 'max:255'],
            'newCustomerPhone' => ['nullable', 'required_without:newCustomerEmail', 'string', 'max:50'],
            'newCustomerEmail' => ['nullable', 'required_without:newCustomerPhone', 'email', 'max:255'],
            'newCustomerType' => ['required', Rule::enum(CustomerType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'startDate' => __('booking::reservations.fields.start_date'),
            'endDate' => __('booking::reservations.fields.end_date'),
            'customerId' => __('booking::reservations.fields.customer'),
            'newCustomerName' => __('booking::reservations.fields.customer_name'),
            'newCustomerPhone' => __('booking::reservations.fields.customer_phone'),
            'newCustomerEmail' => __('booking::reservations.fields.customer_email'),
            'newCustomerType' => __('booking::customers.fields.type'),
        ];
    }

    public function render(): View
    {
        return view('booking::livewire.create-reservation-form', ['customerTypes' => CustomerType::cases()])
            ->title(__('booking::reservations.form.title'));
    }

    private function selectedCustomer(): Customer|NewCustomer
    {
        if (! $this->isNewCustomer) {
            return Customer::query()->findOrFail($this->customerId);
        }

        return new NewCustomer(
            name: $this->newCustomerName,
            phone: $this->newCustomerPhone !== '' ? $this->newCustomerPhone : null,
            email: $this->newCustomerEmail !== '' ? $this->newCustomerEmail : null,
            type: CustomerType::from($this->newCustomerType),
        );
    }
}
