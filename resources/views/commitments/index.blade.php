<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Commitments') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Add Commitment Form --}}
            <div class="bg-white shadow-sm rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Commitment</h3>
                <form method="POST" action="{{ route('commitments.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Commitment Name</label>
                            <input type="text" name="name" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. Netflix" value="{{ old('name') }}">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date Started</label>
                            <input type="date" name="date_started" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                value="{{ old('date_started', now()->format('Y-m-d')) }}">
                            @error('date_started')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount / Month (RM)</label>
                            <input type="number" name="amount_per_month" step="0.01" min="0.01" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. 55.00" value="{{ old('amount_per_month') }}">
                            @error('amount_per_month')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                            <select name="type" id="commitment_type" onchange="toggleTypeFields()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="infinite" {{ old('type', 'infinite') === 'infinite' ? 'selected' : '' }}>Infinite</option>
                                <option value="normal" {{ old('type') === 'normal' ? 'selected' : '' }}>Normal (End Date)</option>
                                <option value="normal-month" {{ old('type') === 'normal-month' ? 'selected' : '' }}>Normal (No. of Months)</option>
                            </select>
                            @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div id="end_date_field" class="{{ old('type') === 'normal' ? '' : 'hidden' }}">
                            <label class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                value="{{ old('end_date') }}">
                            @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div id="num_months_field" class="{{ old('type') === 'normal-month' ? '' : 'hidden' }}">
                            <label class="block text-xs font-medium text-gray-600 mb-1">No. of Months</label>
                            <input type="number" name="num_months" id="num_months" min="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. 12" value="{{ old('num_months') }}">
                            @error('num_months')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2 rounded-lg transition">
                            + Add Commitment
                        </button>
                    </div>
                </form>
            </div>

            {{-- Commitment List --}}
            @forelse($commitments as $commitment)
                @php
                    $paidCount  = $commitment->paid_months_count;
                    $total      = $commitment->total_months;
                    $remaining  = $commitment->months_remaining;
                    $amountPaid = $commitment->amount_paid;
                    $amountLeft = $commitment->amount_remaining;
                    $perMonth   = $commitment->amount_per_month;
                    $progress   = $total > 0 ? ($paidCount / $total) * 100 : 0;

                    $thisYearMonth = now()->format('Y-m');
                    $currentPayment = $commitment->payments->first(fn($p) => str_starts_with($p->month_date, $thisYearMonth));
                    $isCompleted = in_array($commitment->type, ['normal', 'normal-month']) && $commitment->end_date && now()->gt($commitment->end_date) && $remaining === 0;
                    $paymentNotDueYet = $currentPayment && $currentPayment->month_date > now()->format('Y-m-d');
                    $isOnTrack   = !$isCompleted && ($paymentNotDueYet || ($currentPayment && $currentPayment->is_paid));

                    if ($isCompleted) {
                        $statusLabel = '✓ Completed';
                        $statusClass = 'bg-green-500 text-white';
                    } elseif ($isOnTrack) {
                        $statusLabel = '✓ On Track';
                        $statusClass = 'bg-yellow-400 text-white';
                    } else {
                        $statusLabel = '✗ Off Track';
                        $statusClass = 'bg-red-500 text-white';
                    }
                @endphp

                <div class="bg-white shadow-sm rounded-2xl overflow-hidden">

                    {{-- Header row --}}
                    <div class="px-6 pt-5 pb-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $commitment->name }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    RM{{ number_format($perMonth, 2) }}/month
                                    &bull; Started: {{ \Carbon\Carbon::parse($commitment->date_started)->format('d M Y') }}
                                    @if(in_array($commitment->type, ['normal', 'normal-month']) && $commitment->end_date)
                                        &bull; Ends: {{ \Carbon\Carbon::parse($commitment->end_date)->format('d M Y') }}
                                    @else
                                        &bull; <span class="text-purple-500 font-medium">Infinite</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <span id="status-{{ $commitment->id }}"
                                    class="text-sm px-3 py-1 rounded-full font-bold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>

                                <span class="text-sm bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium" id="badge-{{ $commitment->id }}" data-type="{{ $commitment->type }}">
                                    @if($commitment->type === 'infinite')
                                        {{ $paidCount }} months paid
                                    @else
                                        {{ $paidCount }}/{{ $total }} months paid
                                    @endif
                                </span>

                                <button onclick="toggleDetail('detail-{{ $commitment->id }}')"
                                    class="flex items-center gap-1.5 border border-blue-300 text-blue-700 hover:bg-blue-50 text-sm font-medium px-3 py-1.5 rounded-lg transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h7"/>
                                    </svg>
                                    Detail
                                </button>

                                <form method="POST" action="{{ route('commitments.destroy', $commitment) }}" onsubmit="return confirm('Delete this commitment?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                id="progress-{{ $commitment->id }}"
                                style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    {{-- Detail section --}}
                    <div id="detail-{{ $commitment->id }}" class="border-t border-gray-100 hidden">

                        {{-- Summary stats --}}
                        <div class="grid grid-cols-3 sm:grid-cols-{{ $commitment->type === 'infinite' ? '3' : '5' }} gap-3 px-6 py-5">
                            <div class="bg-blue-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600" id="total-months-{{ $commitment->id }}">
                                    {{ $commitment->type === 'infinite' ? '∞' : $total }}
                                </p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Total Months</p>
                            </div>
                            <div class="bg-green-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-green-600" id="paid-months-{{ $commitment->id }}">{{ $paidCount }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Months Paid</p>
                            </div>
                            @if($commitment->type !== 'infinite')
                            <div class="bg-red-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-red-500" id="remaining-months-{{ $commitment->id }}">{{ $remaining }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Months Remaining</p>
                            </div>
                            @endif
                            <div class="bg-blue-50 rounded-xl p-4 text-center">
                                <p class="text-xl font-bold text-blue-600" id="amount-paid-{{ $commitment->id }}">RM{{ number_format($amountPaid, 2) }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Amount Paid</p>
                            </div>
                            @if($commitment->type !== 'infinite')
                            <div class="bg-red-50 rounded-xl p-4 text-center">
                                <p class="text-xl font-bold text-red-500" id="amount-remaining-{{ $commitment->id }}">RM{{ number_format($amountLeft, 2) }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Amount Remaining</p>
                            </div>
                            @endif
                        </div>

                        {{-- Payment table --}}
                        <div class="px-6 pb-6">
                            @php
                                $years = $commitment->payments
                                    ->sortBy('month_date')
                                    ->groupBy(fn($p) => substr($p->month_date, 0, 4))
                                    ->keys();
                                $currentYear = now()->year;
                                $defaultYear = $years->contains((string)$currentYear) ? (string)$currentYear : $years->first();
                            @endphp

                            {{-- Year tabs --}}
                            <div class="flex gap-2 mb-4 overflow-x-auto pb-2 flex-nowrap" id="year-tabs-{{ $commitment->id }}">
                                @foreach($years as $year)
                                    <button
                                        onclick="switchYear('{{ $commitment->id }}', '{{ $year }}')"
                                        id="tab-{{ $commitment->id }}-{{ $year }}"
                                        class="px-4 py-1.5 rounded-full text-sm font-medium border transition
                                            {{ $year == $defaultYear
                                                ? 'bg-blue-600 text-white border-blue-600'
                                                : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-600' }}">
                                        {{ $year }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Tables per year --}}
                            @foreach($commitment->payments->sortBy('month_date')->groupBy(fn($p) => substr($p->month_date, 0, 4)) as $year => $yearPayments)
                                @php $allPaid = $yearPayments->every(fn($p) => $p->is_paid); @endphp
                                <div id="year-table-{{ $commitment->id }}-{{ $year }}"
                                    class="{{ $year == $defaultYear ? '' : 'hidden' }} overflow-x-auto">

                                    {{-- Mark whole year checkbox --}}
                                    <div class="flex items-center gap-2 mb-3">
                                        <input type="checkbox"
                                            id="year-check-{{ $commitment->id }}-{{ $year }}"
                                            {{ $allPaid ? 'checked' : '' }}
                                            onchange="toggleYear(this, '{{ $commitment->id }}', '{{ $year }}', '{{ route('commitments.toggleYear', $commitment) }}')"
                                            class="w-4 h-4 accent-blue-600 cursor-pointer">
                                        <label for="year-check-{{ $commitment->id }}-{{ $year }}" class="text-sm font-semibold text-gray-700 cursor-pointer">
                                            Mark {{ $year }} as fully paid
                                        </label>
                                    </div>

                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-slate-700 text-white">
                                                <th class="py-3 px-4 text-center font-semibold rounded-tl-lg">No.</th>
                                                <th class="py-3 px-4 text-center font-semibold">Month</th>
                                                <th class="py-3 px-4 text-center font-semibold">Amount (RM)</th>
                                                <th class="py-3 px-4 text-center font-semibold rounded-tr-lg">Paid?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($yearPayments->values() as $i => $payment)
                                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                                    <td class="py-3 px-4 text-center text-gray-700">{{ $i + 1 }}</td>
                                                    <td class="py-3 px-4 text-center text-gray-700">{{ \Carbon\Carbon::parse($payment->month_date)->format('d M Y') }}</td>
                                                    <td class="py-3 px-4 text-center text-gray-700">RM{{ number_format($perMonth, 2) }}</td>
                                                    <td class="py-3 px-4 text-center">
                                                        <button type="button"
                                                            data-toggle-url="{{ route('commitments.toggle', [$commitment, $payment]) }}"
                                                            data-commitment-id="{{ $commitment->id }}"
                                                            onclick="togglePayment(this)"
                                                            class="w-5 h-5 rounded border-2 {{ $payment->is_paid ? 'bg-blue-500 border-blue-500' : 'border-gray-400 bg-white' }} inline-flex items-center justify-center transition hover:border-blue-400">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white check-icon {{ $payment->is_paid ? '' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @empty
                <div class="bg-white shadow-sm rounded-2xl p-10 text-center text-gray-400">
                    <p class="text-lg">No commitments yet. Add one above!</p>
                </div>
            @endforelse

        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        function toggleTypeFields() {
            const type = document.getElementById('commitment_type').value;
            const endDateField   = document.getElementById('end_date_field');
            const numMonthsField = document.getElementById('num_months_field');
            const endDateInput   = document.getElementById('end_date');
            const numMonthsInput = document.getElementById('num_months');

            endDateField.classList.add('hidden');
            numMonthsField.classList.add('hidden');
            endDateInput.required   = false;
            endDateInput.value      = '';
            numMonthsInput.required = false;
            numMonthsInput.value    = '';

            if (type === 'normal') {
                endDateField.classList.remove('hidden');
                endDateInput.required = true;
            } else if (type === 'normal-month') {
                numMonthsField.classList.remove('hidden');
                numMonthsInput.required = true;
            }
        }

        function toggleYear(checkbox, cId, year, url) {
            const isPaid = checkbox.checked;
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ year: parseInt(year), is_paid: isPaid }),
            })
            .then(r => r.json())
            .then(data => {
                // Update all row checkboxes in this year
                document.querySelectorAll(`#year-table-${cId}-${year} button[data-commitment-id]`).forEach(btn => {
                    if (isPaid) {
                        btn.classList.add('bg-blue-500', 'border-blue-500');
                        btn.classList.remove('border-gray-400', 'bg-white');
                        btn.querySelector('.check-icon').classList.remove('hidden');
                    } else {
                        btn.classList.remove('bg-blue-500', 'border-blue-500');
                        btn.classList.add('border-gray-400', 'bg-white');
                        btn.querySelector('.check-icon').classList.add('hidden');
                    }
                });

                // Update stats
                document.getElementById('paid-months-' + cId).textContent = data.paid_months;
                const remainingEl = document.getElementById('remaining-months-' + cId);
                if (remainingEl) remainingEl.textContent = data.months_remaining;
                document.getElementById('amount-paid-' + cId).textContent = 'RM' + data.amount_paid;
                const amountRemainingEl = document.getElementById('amount-remaining-' + cId);
                if (amountRemainingEl) amountRemainingEl.textContent = 'RM' + data.amount_remaining;
                const badgeEl = document.getElementById('badge-' + cId);
                badgeEl.textContent = badgeEl.dataset.type === 'infinite'
                    ? data.paid_months + ' months paid'
                    : data.paid_months + '/' + data.total_months + ' months paid';
                document.getElementById('progress-' + cId).style.width = data.progress + '%';

                const statusEl = document.getElementById('status-' + cId);
                if (data.is_completed) {
                    statusEl.textContent = '✓ Completed';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-green-500 text-white';
                } else if (data.is_on_track) {
                    statusEl.textContent = '✓ On Track';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-yellow-400 text-white';
                } else {
                    statusEl.textContent = '✗ Off Track';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-red-500 text-white';
                }
            });
        }

        function toggleDetail(id) {
            document.getElementById(id).classList.toggle('hidden');
        }

        function switchYear(cId, year) {
            // Hide all year tables for this commitment
            document.querySelectorAll(`[id^="year-table-${cId}-"]`).forEach(el => el.classList.add('hidden'));
            // Show selected
            document.getElementById(`year-table-${cId}-${year}`).classList.remove('hidden');
            // Update tab styles
            document.querySelectorAll(`#year-tabs-${cId} button`).forEach(btn => {
                btn.className = 'px-4 py-1.5 rounded-full text-sm font-medium border transition bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-600';
            });
            document.getElementById(`tab-${cId}-${year}`).className = 'px-4 py-1.5 rounded-full text-sm font-medium border transition bg-blue-600 text-white border-blue-600';
        }

        function togglePayment(btn) {
            const url = btn.dataset.toggleUrl;
            const cId = btn.dataset.commitmentId;

            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.is_paid) {
                    btn.classList.add('bg-blue-500', 'border-blue-500');
                    btn.classList.remove('border-gray-400', 'bg-white');
                    btn.querySelector('.check-icon').classList.remove('hidden');
                } else {
                    btn.classList.remove('bg-blue-500', 'border-blue-500');
                    btn.classList.add('border-gray-400', 'bg-white');
                    btn.querySelector('.check-icon').classList.add('hidden');
                }

                document.getElementById('paid-months-' + cId).textContent     = data.paid_months;
                const remainingEl = document.getElementById('remaining-months-' + cId);
                if (remainingEl) remainingEl.textContent = data.months_remaining;
                document.getElementById('amount-paid-' + cId).textContent      = 'RM' + data.amount_paid;
                const amountRemainingEl = document.getElementById('amount-remaining-' + cId);
                if (amountRemainingEl) amountRemainingEl.textContent = 'RM' + data.amount_remaining;

                const badgeEl = document.getElementById('badge-' + cId);
                const isInfinite = badgeEl.dataset.type === 'infinite';
                badgeEl.textContent = isInfinite
                    ? data.paid_months + ' months paid'
                    : data.paid_months + '/' + data.total_months + ' months paid';

                document.getElementById('progress-' + cId).style.width = data.progress + '%';

                const statusEl = document.getElementById('status-' + cId);
                if (data.is_completed) {
                    statusEl.textContent = '✓ Completed';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-green-500 text-white';
                } else if (data.is_on_track) {
                    statusEl.textContent = '✓ On Track';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-yellow-400 text-white';
                } else {
                    statusEl.textContent = '✗ Off Track';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-bold bg-red-500 text-white';
                }
            });
        }
    </script>
</x-app-layout>
