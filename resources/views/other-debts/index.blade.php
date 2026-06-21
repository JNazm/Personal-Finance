<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Debt From Others') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Add Form --}}
            <div class="bg-white shadow-sm rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Debt From Others</h3>
                <form method="POST" action="{{ route('other-debts.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Person Name</label>
                            <input type="text" name="person" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. Along" value="{{ old('person') }}">
                            @error('person')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Debt Name</label>
                            <input type="text" name="name" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. Hutang laptop" value="{{ old('name') }}">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Total Amount (RM)</label>
                            <input type="number" name="total_amount" id="total_amount" step="0.01" min="0.01" required
                                oninput="calcPayment()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. 1000" value="{{ old('total_amount') }}">
                            @error('total_amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">No. of Months</label>
                            <input type="number" name="months" id="months" min="1" required
                                oninput="calcPayment()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                placeholder="e.g. 12" value="{{ old('months') }}">
                            @error('months')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Payment / Month (Auto)</label>
                            <input type="text" id="payment_per_month" readonly
                                class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                placeholder="RM 0.00">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Start Month</label>
                            <input type="month" name="start_month" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                value="{{ old('start_month', now()->format('Y-m')) }}">
                            @error('start_month')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2 rounded-lg transition">
                            + Add Debt
                        </button>
                    </div>
                </form>
            </div>

            {{-- Debt List --}}
            @forelse($otherDebts as $debt)
                @php
                    $paidCount  = $debt->paid_months_count;
                    $remaining  = $debt->months_remaining;
                    $amountPaid = $debt->amount_paid;
                    $amountLeft = $debt->amount_remaining;
                    $perMonth   = $debt->payment_per_month;
                    $progress   = $debt->months > 0 ? ($paidCount / $debt->months) * 100 : 0;
                    $startDate  = \Carbon\Carbon::parse($debt->start_month);
                    $isCompleted = $paidCount >= $debt->months;
                @endphp

                <div class="bg-white shadow-sm rounded-2xl overflow-hidden">

                    {{-- Header row --}}
                    <div class="px-6 pt-5 pb-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">{{ $debt->person }}</span>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $debt->name }}</h3>
                                </div>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    RM{{ number_format($perMonth, 2) }}/month
                                    &bull; {{ $debt->months }} months
                                    &bull; Total: RM{{ number_format($debt->total_amount, 2) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <span id="status-{{ $debt->id }}"
                                    class="text-sm px-3 py-1 rounded-full font-medium {{ $isCompleted ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $isCompleted ? '✓ Completed' : '⏳ Pending' }}
                                </span>

                                <span class="text-sm bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium" id="badge-{{ $debt->id }}">
                                    {{ $paidCount }}/{{ $debt->months }} months paid
                                </span>

                                <button onclick="toggleDetail('detail-{{ $debt->id }}')"
                                    class="flex items-center gap-1.5 border border-blue-300 text-blue-700 hover:bg-blue-50 text-sm font-medium px-3 py-1.5 rounded-lg transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h7"/>
                                    </svg>
                                    Detail
                                </button>

                                <form method="POST" action="{{ route('other-debts.destroy', $debt) }}" onsubmit="return confirm('Delete this debt?')">
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
                                id="progress-{{ $debt->id }}"
                                style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    {{-- Detail section --}}
                    <div id="detail-{{ $debt->id }}" class="border-t border-gray-100 hidden">

                        {{-- Summary stats --}}
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 px-6 py-5">
                            <div class="bg-blue-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ $debt->months }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Total Months</p>
                            </div>
                            <div class="bg-green-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-green-600" id="paid-months-{{ $debt->id }}">{{ $paidCount }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Months Paid</p>
                            </div>
                            <div class="bg-red-50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-red-500" id="remaining-months-{{ $debt->id }}">{{ $remaining }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Months Remaining</p>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-4 text-center">
                                <p class="text-xl font-bold text-blue-600" id="amount-paid-{{ $debt->id }}">RM{{ number_format($amountPaid, 2) }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Amount Paid</p>
                            </div>
                            <div class="bg-red-50 rounded-xl p-4 text-center">
                                <p class="text-xl font-bold text-red-500" id="amount-remaining-{{ $debt->id }}">RM{{ number_format($amountLeft, 2) }}</p>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wide">Amount Remaining</p>
                            </div>
                        </div>

                        {{-- Payment table --}}
                        <div class="px-6 pb-6 overflow-x-auto">
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
                                    @foreach($debt->payments->sortBy('month_index') as $payment)
                                        @php
                                            $monthDate = $startDate->copy()->addMonths($payment->month_index - 1);
                                        @endphp
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td class="py-3 px-4 text-center text-gray-700">{{ $payment->month_index }}</td>
                                            <td class="py-3 px-4 text-center text-gray-700">{{ $monthDate->format('M Y') }}</td>
                                            <td class="py-3 px-4 text-center text-gray-700">RM{{ number_format($perMonth, 2) }}</td>
                                            <td class="py-3 px-4 text-center">
                                                <button type="button"
                                                    data-toggle-url="{{ route('other-debts.toggle', [$debt, $payment->month_index]) }}"
                                                    data-debt-id="{{ $debt->id }}"
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
                    </div>

                </div>
            @empty
                <div class="bg-white shadow-sm rounded-2xl p-10 text-center text-gray-400">
                    <p class="text-lg">No debts from others yet. Add one above!</p>
                </div>
            @endforelse

        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        function calcPayment() {
            const amt = parseFloat(document.getElementById('total_amount').value);
            const mo  = parseInt(document.getElementById('months').value);
            document.getElementById('payment_per_month').value = (amt > 0 && mo > 0) ? 'RM ' + (amt / mo).toFixed(2) : 'RM 0.00';
        }

        function toggleDetail(id) {
            document.getElementById(id).classList.toggle('hidden');
        }

        function togglePayment(btn) {
            const url    = btn.dataset.toggleUrl;
            const debtId = btn.dataset.debtId;

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

                document.getElementById('paid-months-' + debtId).textContent      = data.paid_months;
                document.getElementById('remaining-months-' + debtId).textContent  = data.months_remaining;
                document.getElementById('amount-paid-' + debtId).textContent       = 'RM' + data.amount_paid;
                document.getElementById('amount-remaining-' + debtId).textContent  = 'RM' + data.amount_remaining;
                document.getElementById('badge-' + debtId).textContent             = data.paid_months + '/' + data.total_months + ' months paid';
                document.getElementById('progress-' + debtId).style.width          = data.progress + '%';

                const statusEl = document.getElementById('status-' + debtId);
                if (data.paid_months >= data.total_months) {
                    statusEl.textContent = '✓ Completed';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-medium bg-green-100 text-green-700';
                } else {
                    statusEl.textContent = '⏳ Pending';
                    statusEl.className = 'text-sm px-3 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700';
                }
            });
        }
    </script>
</x-app-layout>
