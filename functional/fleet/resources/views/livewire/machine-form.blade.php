<div class="flex max-w-2xl flex-col gap-6">
    <x-page-heading :title="$title" />

    @error('refusal')
        <flux:callout variant="danger" icon="x-circle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:input wire:model="reference" :label="__('fleet::machines.fields.reference')" />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="categoryId" :label="__('fleet::machines.fields.category')">
                <flux:select.option value="">{{ __('fleet::machines.form.choose') }}</flux:select.option>
                @foreach ($categories as $category)
                    <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="agencyId" :label="__('fleet::machines.fields.agency')">
                <flux:select.option value="">{{ __('fleet::machines.form.choose') }}</flux:select.option>
                @foreach ($agencies as $agency)
                    <flux:select.option :value="$agency->id">{{ $agency->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid items-end gap-4 md:grid-cols-2">
            <flux:checkbox wire:model="isSubjectToVgp" :label="__('fleet::machines.fields.is_subject_to_vgp')"
                :description="__('fleet::machines.form.vgp_forced_by_category')" />
            <flux:input type="date" wire:model="vgpDueDate" :label="__('fleet::machines.fields.vgp_due_date')" />
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('fleet::machines.form.save') }}</flux:button>
            <flux:button variant="ghost" wire:navigate :href="route('machines.index')">{{ __('fleet::machines.form.back') }}</flux:button>
        </div>
    </form>
</div>
