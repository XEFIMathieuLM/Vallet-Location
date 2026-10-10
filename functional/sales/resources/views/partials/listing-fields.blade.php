<div class="grid gap-4 md:grid-cols-3">
    <flux:input wire:model="askingPrice" :label="__('sales::sales.fields.asking_price_input')" :disabled="! $isAskingPriceEditable" />
    <flux:input type="number" wire:model="yearOfManufacture" :label="__('sales::sales.fields.year_of_manufacture')" />
    <flux:input type="number" wire:model="operatingHours" :label="__('sales::sales.fields.operating_hours')" />
</div>
<flux:input wire:model="condition" :label="__('sales::sales.fields.condition')" />
<flux:textarea wire:model="comment" :label="__('sales::sales.fields.comment')" rows="3" />
