<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            {{-- TOP ROW: Pie Chart + Debt Summary --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Pie Chart --}}
                <div class="bg-white shadow-sm rounded-2xl p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4 text-center">Commitment vs Salary</h3>
                    <div class="flex justify-center">
                        <canvas id="pieChart" width="220" height="220"></canvas>
                    </div>
                    <div class="mt-4 space-y-1.5">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                            <span class="text-gray-600">Net Salary</span>
                            <span class="ml-auto font-semibold text-gray-800">{{ $pieValues[0] }}%</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span>
                            <span class="text-gray-600">Monthly Commitments</span>
                            <span class="ml-auto font-semibold text-gray-800">{{ $pieValues[1] }}%</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                            <span class="text-gray-600">Short-Term Debts</span>
                            <span class="ml-auto font-semibold text-gray-800">{{ $pieValues[2] }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Short Term Debt Left This Month --}}
                <div class="lg:col-span-2 bg-white shadow-sm rounded-2xl p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4 text-center">Short Term Debt Left This Month</h3>
                    <div class="grid grid-cols-2 gap-4 h-36">
                        <div class="rounded-xl flex flex-col items-center justify-center {{ $myDebtThisMonthTotal > 0 ? 'bg-red-600' : 'bg-green-600' }}">
                            <p class="text-white text-xs font-semibold uppercase tracking-wide mb-1">From Me</p>
                            <p class="text-white text-2xl font-bold">RM{{ number_format($myDebtThisMonthTotal, 2) }}</p>
                        </div>
                        <div class="rounded-xl flex flex-col items-center justify-center {{ $otherDebtThisMonthTotal > 0 ? 'bg-red-600' : 'bg-green-600' }}">
                            <p class="text-white text-xs font-semibold uppercase tracking-wide mb-1">From Others</p>
                            <p class="text-white text-2xl font-bold">RM{{ number_format($otherDebtThisMonthTotal, 2) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                        <div class="bg-blue-50 rounded-xl p-3">
                            <p class="text-lg font-bold text-blue-600">RM{{ number_format($salary, 2) }}</p>
                            <p class="text-xs text-gray-500 uppercase mt-0.5">Monthly Salary</p>
                        </div>
                        <div class="bg-yellow-50 rounded-xl p-3">
                            <p class="text-lg font-bold text-yellow-600">RM{{ number_format($totalCommitmentPerMonth, 2) }}</p>
                            <p class="text-xs text-gray-500 uppercase mt-0.5">Commitments/Mo</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3">
                            <p class="text-lg font-bold text-red-600">RM{{ number_format($totalDebtPerMonth + $totalOtherDebtPerMonth, 2) }}</p>
                            <p class="text-xs text-gray-500 uppercase mt-0.5">Debts/Mo</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BOTTOM ROW: Three Tables --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Commitments Table --}}
                <div class="bg-white shadow-sm rounded-2xl overflow-hidden">
                    <div class="bg-slate-700 px-4 py-3">
                        <h3 class="text-white text-sm font-bold text-center uppercase tracking-wide">Commitments</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="py-2 px-3 text-left text-gray-500">#</th>
                                    <th class="py-2 px-3 text-left text-gray-500">Name</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Month</th>
                                    <th class="py-2 px-3 text-right text-gray-500">Amount</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commitmentsThisMonth as $i => $c)
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-gray-500">{{ $i+1 }}</td>
                                        <td class="py-2 px-3 text-gray-800 font-medium">{{ $c['name'] }}</td>
                                        <td class="py-2 px-3 text-center text-gray-600">{{ $c['month'] }}</td>
                                        <td class="py-2 px-3 text-right text-gray-800">RM{{ number_format($c['amount'], 2) }}</td>
                                        <td class="py-2 px-3 text-center">
                                            @if($c['is_paid'])
                                                <span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded font-semibold">Good</span>
                                            @else
                                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded font-semibold">Not Good</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-gray-400">No commitments</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Short Term Debt From Me --}}
                <div class="bg-white shadow-sm rounded-2xl overflow-hidden">
                    <div class="bg-slate-700 px-4 py-3">
                        <h3 class="text-white text-sm font-bold text-center uppercase tracking-wide">Short Term Debt From Me</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="py-2 px-3 text-left text-gray-500">#</th>
                                    <th class="py-2 px-3 text-left text-gray-500">Name</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Left</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Month</th>
                                    <th class="py-2 px-3 text-right text-gray-500">Amount</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($debtsThisMonth as $i => $d)
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-gray-500">{{ $i+1 }}</td>
                                        <td class="py-2 px-3 text-gray-800 font-medium">{{ $d['name'] }}</td>
                                        <td class="py-2 px-3 text-center text-gray-600">x{{ $d['months_remaining'] }}</td>
                                        <td class="py-2 px-3 text-center text-gray-600">{{ $d['month'] }}</td>
                                        <td class="py-2 px-3 text-right text-gray-800">RM{{ number_format($d['amount'], 2) }}</td>
                                        <td class="py-2 px-3 text-center">
                                            @if($d['completed'])
                                                <span class="bg-yellow-500 text-white text-xs px-2 py-0.5 rounded font-semibold">Completed</span>
                                            @elseif($d['is_paid'])
                                                <span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded font-semibold">Good</span>
                                            @else
                                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded font-semibold">Not Good</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-6 text-center text-gray-400">No debts</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Short Term Debt From Others --}}
                <div class="bg-white shadow-sm rounded-2xl overflow-hidden">
                    <div class="bg-slate-700 px-4 py-3">
                        <h3 class="text-white text-sm font-bold text-center uppercase tracking-wide">Short Term Debt From Others</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="py-2 px-3 text-left text-gray-500">#</th>
                                    <th class="py-2 px-3 text-left text-gray-500">Person</th>
                                    <th class="py-2 px-3 text-left text-gray-500">Name</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Left</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Month</th>
                                    <th class="py-2 px-3 text-right text-gray-500">Amount</th>
                                    <th class="py-2 px-3 text-center text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($otherDebtsThisMonth as $i => $d)
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-gray-500">{{ $i+1 }}</td>
                                        <td class="py-2 px-3 text-gray-800 font-medium">{{ $d['person'] }}</td>
                                        <td class="py-2 px-3 text-gray-700">{{ $d['name'] }}</td>
                                        <td class="py-2 px-3 text-center text-gray-600">x{{ $d['months_remaining'] }}</td>
                                        <td class="py-2 px-3 text-center text-gray-600">{{ $d['month'] }}</td>
                                        <td class="py-2 px-3 text-right text-gray-800">RM{{ number_format($d['amount'], 2) }}</td>
                                        <td class="py-2 px-3 text-center">
                                            @if($d['completed'])
                                                <span class="bg-yellow-500 text-white text-xs px-2 py-0.5 rounded font-semibold">Completed</span>
                                            @elseif($d['is_paid'])
                                                <span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded font-semibold">Good</span>
                                            @else
                                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded font-semibold">Not Good</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-6 text-center text-gray-400">No other debts</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: {!! json_encode($pieLabels) !!},
                datasets: [{
                    data: {!! json_encode($pieValues) !!},
                    backgroundColor: ['#3b82f6', '#facc15', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.label + ': ' + ctx.parsed + '%'
                        }
                    }
                },
                responsive: false,
            }
        });

        function calcOdPayment() {
            const amt = parseFloat(document.getElementById('od_total').value);
            const mo  = parseInt(document.getElementById('od_months').value);
            document.getElementById('od_payment').value = (amt > 0 && mo > 0) ? 'RM ' + (amt / mo).toFixed(2) : 'RM 0.00';
        }
    </script>
</x-app-layout>
