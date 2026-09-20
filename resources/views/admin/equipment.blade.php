@extends('components.default')
@section('title', 'Equipment - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $totalUnits = $equipment->sum('quantity');
    $lendable = $equipment->reject(fn ($item) => $item->isRetired());
    $availableUnits = $lendable->sum('available_quantity');
    $unitsOut = $equipment->sum(fn ($item) => $item->outNow());
    $lowCount = $lendable->filter(fn ($item) => $item->availabilityState()['key'] === 'low')->count();
    $fullyOut = $lendable->filter(fn ($item) => $item->available_quantity <= 0)->count();
    $itemTypesOut = $equipment->filter(fn ($item) => $item->outNow() > 0)->count();
    $retiredCount = $equipment->count() - $lendable->count();
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Equipment" title="Inventory">
        {{ $equipment->count() }} item {{ str('type')->plural($equipment->count()) }} · {{ $totalUnits }} {{ str('unit')->plural($totalUnits) }}
        <x-slot:actions>
            <button type="button" data-equipment-add
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-plus" aria-hidden="true"></i> Add equipment
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if($equipment->isNotEmpty())
            <x-ui.stat-strip :stats="[
                ['label' => 'Units on the shelf', 'value' => $availableUnits, 'unit' => 'of '.$totalUnits, 'sub' => 'Ready to lend right now'],
                ['label' => 'Checked out', 'value' => $unitsOut, 'unit' => $unitsOut === 1 ? 'unit' : 'units', 'sub' => 'Across '.$itemTypesOut.' item '.str('type')->plural($itemTypesOut)],
                ['label' => 'Running low', 'value' => $lowCount, 'unit' => '', 'sub' => 'Under 30% available', 'tone' => $lowCount > 0 ? 'warning' : 'neutral'],
                ['label' => 'Fully out', 'value' => $fullyOut, 'unit' => '', 'sub' => 'Nothing left to lend', 'tone' => $fullyOut > 0 ? 'danger' : 'neutral'],
            ]" />
        @endif

        <x-ui.panel data-list data-active-chip="all">
            @if($equipment->isEmpty())
                <x-ui.empty-state icon="fa-toolbox" title="No equipment yet"
                                  message="Add your first item to start tracking what the department lends out.">
                    <x-slot:action>
                        <button type="button" class="btn-primary" data-equipment-add>
                            <i class="text-base fas fa-plus" aria-hidden="true"></i> Add equipment
                        </button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                {{-- Four chips and a search box, in place of the "Show 10 entries"
                     select, the pager and the six sort arrows that used to sit
                     above an eight-row table. --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter equipment">
                        @php
                            $chips = [
                                'all' => 'All '.$equipment->count(),
                                'lendable' => 'Lendable',
                                'low' => 'Low or out',
                                'out' => 'Fully out',
                            ];
                            if ($retiredCount > 0) {
                                $chips['retired'] = 'Retired '.$retiredCount;
                            }
                        @endphp
                        @foreach($chips as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === 'all' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search equipment</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search equipment"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                {{-- Column head. "Quantity" and "Available" were two numeric
                     columns that only made sense read together, so they are one
                     column now: "3 of 10 available", with a bar behind it. --}}
                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 md:grid md:grid-cols-[minmax(0,2.2fr)_minmax(0,1.4fr)_9rem_6rem]">
                    <div>Equipment</div>
                    <div>Availability</div>
                    <div>Status</div>
                    <div class="text-right">Actions</div>
                </div>

                <div class="divide-y divide-neutral-200">
                    @foreach ($equipment as $item)
                        @php
                            $state = $item->availabilityState();
                            $out = $item->outNow();
                            $references = $item->referencesCount();
                            $percent = $item->quantity > 0 ? round(($item->available_quantity / $item->quantity) * 100) : 0;
                            $barTone = [
                                'out' => 'bg-danger-500', 'low' => 'bg-warning-500',
                                'partial' => 'bg-primary-500', 'all-in' => 'bg-success-500',
                                'retired' => 'bg-neutral-300',
                            ][$state['key']];
                            $chipKeys = collect([$state['key']]);
                            if (! $item->isRetired()) {
                                if ($item->available_quantity > 0) { $chipKeys->push('lendable'); }
                                if (in_array($state['key'], ['low', 'out'])) { $chipKeys->push('low'); }
                            }
                            $blocked = $out > 0
                                ? "Can't delete — ".$out.' '.str('unit')->plural($out).' '.($out === 1 ? 'is' : 'are').' still out'
                                : ($references > 0
                                    ? "Can't delete — ".$references.' '.str('record')->plural($references).' reference this item'
                                    : '');
                        @endphp
                        <div data-list-row data-chip="{{ $chipKeys->implode(' ') }}"
                             data-search="{{ strtolower($item->equipment_name.' '.$item->description) }}"
                             class="grid gap-3 px-4 py-4 sm:px-5 md:grid-cols-[minmax(0,2.2fr)_minmax(0,1.4fr)_9rem_6rem] md:items-center md:gap-4 hover:bg-neutral-50">

                            <div class="min-w-0">
                                <p class="text-base font-semibold text-neutral-900">{{ $item->equipment_name }}</p>
                                <p class="mt-0.5 text-sm text-neutral-600 text-pretty">{{ $item->description ?: 'No description yet' }}</p>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm text-neutral-700 tabular-nums">
                                    <span class="font-semibold">{{ $item->available_quantity }}</span> of {{ $item->quantity }} available
                                    @if($out > 0)
                                        <span class="text-neutral-600">· {{ $out }} out</span>
                                    @endif
                                </p>
                                <div class="h-1.5 mt-2 overflow-hidden rounded-full bg-neutral-200" role="presentation">
                                    <div class="h-full rounded-full {{ $barTone }}" style="width: {{ max($percent, 0) }}%"></div>
                                </div>
                            </div>

                            <div>
                                <x-ui.status :label="$state['label']" :tone="$state['tone']" />
                            </div>

                            <div class="flex items-center gap-2 md:justify-end">
                                <button type="button"
                                        class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-700 hover:border-primary-300 hover:text-primary-700"
                                        title="Edit {{ $item->equipment_name }}" aria-label="Edit {{ $item->equipment_name }}"
                                        data-equipment-edit
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->equipment_name }}"
                                        data-description="{{ $item->description }}"
                                        data-quantity="{{ $item->quantity }}"
                                        data-out="{{ $out }}">
                                    <i class="text-base fas fa-pen" aria-hidden="true"></i>
                                </button>

                                <button type="button"
                                        class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-600 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                        title="Remove {{ $item->equipment_name }}" aria-label="Remove {{ $item->equipment_name }}"
                                        data-remove-trigger data-dialog="remove-dialog"
                                        data-title="{{ $item->isRetired() ? 'Restore '.$item->equipment_name.'?' : 'Remove '.$item->equipment_name.'?' }}"
                                        data-body="{{ $item->isRetired()
                                            ? 'This item is retired: it keeps its history but cannot be lent out. Restoring puts it back on the shelf.'
                                            : ($out > 0
                                                ? 'Units of this item are still with borrowers. Retire it and it stops being lendable, but the outstanding loans stay tracked until they come back.'
                                                : 'Retiring keeps every past loan and return in the logs — the item simply stops appearing as lendable. Deleting erases it from those records too.') }}"
                                        data-fact-a="{{ $item->quantity }}"
                                        data-fact-b="{{ $out }}" data-fact-b-alert="{{ $out > 0 ? '1' : '0' }}"
                                        data-fact-c="{{ $references }}"
                                        data-blocked="{{ $blocked }}"
                                        data-delete-url="{{ route('admin.equipment.destroy', $item->id) }}"
                                        data-safe-url="{{ $item->isRetired() ? route('admin.equipment.restore', $item->id) : route('admin.equipment.retire', $item->id) }}"
                                        data-safe-label="{{ $item->isRetired() ? 'Restore item' : 'Retire item' }}"
                                        data-safe-icon="{{ $item->isRetired() ? 'fa-rotate-left' : 'fa-box-archive' }}">
                                    <i class="text-base fas {{ $item->isRetired() ? 'fa-rotate-left' : 'fa-trash' }}" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No equipment matches that filter.
                </p>

                <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $equipment->count() }}" data-noun="items"></span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>

@include('components.admin.equipment.form-modal')

<x-ui.remove-dialog
    id="remove-dialog"
    safe-label="Retire item"
    safe-icon="fa-box-archive"
    cancel-label="Keep item"
    :facts="['Units owned', 'Out with borrowers', 'Loans and requests referencing it']" />

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('equipment-modal');
    if (!modal) return;

    const form = document.getElementById('equipment-form');
    const idField = document.getElementById('equipment-id');
    const nameField = document.getElementById('equipment-name');
    const descField = document.getElementById('equipment-description');
    const qtyField = document.getElementById('equipment-quantity');
    const submit = modal.querySelector('[data-equipment-submit]');
    const hint = modal.querySelector('[data-equipment-hint]');
    const previewText = modal.querySelector('[data-equipment-preview-text]');
    const previewDot = modal.querySelector('[data-equipment-preview-dot]');
    const nameError = modal.querySelector('[data-error-for="equipment-name"]');

    const ADD_URL = @json(route('admin.equipment.store'));
    const UPDATE_URL = @json(route('admin.equipment.update'));

    let unitsOut = 0;
    let editing = false;

    // Validation runs against the loans, not against a guess: the total cannot
    // be pushed below the units that are physically with borrowers, and the
    // submit states which rule is stopping it.
    function sync() {
        const name = nameField.value.trim();
        const qty = parseInt(qtyField.value, 10);
        const nameOk = name.length > 1;
        const qtyOk = !isNaN(qty) && qty >= (editing ? 0 : 1);
        const tooFew = editing && qtyOk && qty < unitsOut;

        nameError.hidden = nameOk || name.length === 0;
        nameError.textContent = 'Give the item a name of at least two characters.';

        if (tooFew) {
            previewText.textContent = unitsOut + ' ' + (unitsOut === 1 ? 'unit is' : 'units are')
                + ' out on loan — the total cannot go below that.';
            previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
        } else if (!qtyOk) {
            previewText.textContent = 'Enter how many units the department owns.';
            previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-neutral-400';
        } else if (editing) {
            previewText.textContent = unitsOut + ' out on loan · will read '
                + (qty - unitsOut) + ' of ' + qty + ' available';
            previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-success-600';
        } else {
            previewText.textContent = 'Will be listed as ' + qty + ' of ' + qty + ' available';
            previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-success-600';
        }

        const ok = nameOk && qtyOk && !tooFew;
        submit.disabled = !ok;
        hint.textContent = !nameOk ? 'Name required'
            : tooFew ? 'Check the units back in first'
            : !qtyOk ? 'Enter a unit count'
            : editing ? 'Saves immediately' : 'Appears in the list at once';
        hint.classList.toggle('text-danger-700', !ok);
        hint.classList.toggle('text-neutral-600', ok);
    }

    function open(data) {
        editing = !!(data && data.id);
        unitsOut = parseInt((data && data.out) || '0', 10) || 0;

        form.action = editing ? UPDATE_URL : ADD_URL;
        idField.value = editing ? data.id : '';
        nameField.value = editing ? data.name : '';
        descField.value = editing ? (data.description || '') : '';
        qtyField.value = editing ? data.quantity : 1;
        qtyField.min = editing ? Math.max(unitsOut, 0) : 1;

        modal.querySelector('[data-equipment-title]').textContent = editing ? 'Edit equipment' : 'Add equipment';
        modal.querySelector('[data-equipment-subtitle]').textContent = editing
            ? 'Availability is tracked from loans — adjust the total only when units are added, retired or lost.'
            : 'New items start fully available. Availability updates itself as things are lent out.';
        modal.querySelector('[data-quantity-label]').textContent = editing ? 'Total units owned' : 'Units to add';
        modal.querySelector('[data-equipment-submit-label]').textContent = editing ? 'Save changes' : 'Add to inventory';

        sync();
        window.appUI.openModal('equipment-modal');
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-equipment-add]')) { open(null); return; }

        const edit = event.target.closest('[data-equipment-edit]');
        if (edit) { open(edit.dataset); return; }

        const step = event.target.closest('[data-step]');
        if (step && modal.contains(step)) {
            const floor = parseInt(qtyField.min, 10) || 0;
            const next = (parseInt(qtyField.value, 10) || 0) + parseInt(step.dataset.step, 10);
            qtyField.value = Math.max(floor, next);
            sync();
        }
    });

    [nameField, qtyField].forEach(function (field) {
        field.addEventListener('input', sync);
    });

    sync();
});
</script>
@endsection
