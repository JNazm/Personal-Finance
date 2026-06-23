<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user        = auth()->user();
        $now         = Carbon::now();
        $thisMonth   = $now->format('Y-m');
        $thisMonthDate = $now->copy()->startOfMonth()->toDateString();

        // --- Commitments ---
        $commitments = $user->commitments()->with('payments')->latest()->get();
        foreach ($commitments as $c) {
            $c->syncPayments();
        }
        $commitments = $user->commitments()->with('payments')->latest()->get();

        $totalCommitmentPerMonth = $commitments->sum('amount_per_month');
        $adjustedCommitmentPerMonth = max(0, $totalCommitmentPerMonth - 300);

        $commitmentsThisMonth = $commitments->map(function ($c) use ($thisMonth) {
            $payment = $c->payments->first(fn($p) => str_starts_with($p->month_date, $thisMonth));
            $notDueYet = $payment && $payment->month_date > now()->format('Y-m-d');
            $isOnTrack = $notDueYet || ($payment && $payment->is_paid);
            return [
                'name'      => $c->name,
                'month'     => now()->format('m-Y'),
                'amount'    => $c->amount_per_month,
                'is_paid'   => $payment ? $payment->is_paid : false,
                'on_track'  => $isOnTrack,
            ];
        });

        // --- My Debts ---
        $debts = $user->debts()->with('payments')->latest()->get();

        $activeDebts = $debts->filter(fn($d) => $d->months_remaining > 0);
        $totalDebtPerMonth = $activeDebts->sum('payment_per_month');

        $debtsThisMonth = $debts->map(function ($d) use ($now) {
            $startDate = Carbon::parse($d->start_month);
            $monthIndex = (int) $startDate->diffInMonths($now) + 1;
            $payment = null;
            if ($monthIndex >= 1 && $monthIndex <= $d->months) {
                $payment = $d->payments->firstWhere('month_index', $monthIndex);
            }

            $lastPaidPayment = $d->payments->where('is_paid', true)->sortByDesc('month_index')->first();
            $lastPaidMonth = $lastPaidPayment
                ? $startDate->copy()->addMonths($lastPaidPayment->month_index - 1)->format('d-m-Y')
                : '-';

            $isCompleted = $d->months_remaining <= 0;
            $isOnTrack   = $payment && $payment->is_paid;

            return [
                'name'            => $d->name,
                'months_remaining'=> $d->months_remaining,
                'month'           => $lastPaidMonth,
                'amount'          => $d->payment_per_month,
                'amount_remaining'=> $d->amount_remaining,
                'is_paid'         => $payment ? $payment->is_paid : false,
                'completed'       => $isCompleted,
                'on_track'        => $isOnTrack,
            ];
        });

        $myDebtThisMonthTotal = $debtsThisMonth
            ->filter(fn($d) => !$d['completed'] && !$d['is_paid'])
            ->sum('amount');

        // --- Other Debts ---
        $otherDebts = $user->otherDebts()->with('payments')->latest()->get();
        $totalOtherDebtPerMonth = $otherDebts->filter(fn($d) => $d->months_remaining > 0)->sum('payment_per_month');

        $otherDebtsThisMonth = $otherDebts->map(function ($d) use ($now) {
            $startDate = Carbon::parse($d->start_month);
            $monthIndex = (int) $startDate->diffInMonths($now) + 1;
            $payment = null;
            if ($monthIndex >= 1 && $monthIndex <= $d->months) {
                $payment = $d->payments->firstWhere('month_index', $monthIndex);
            }

            $lastPaidPayment = $d->payments->where('is_paid', true)->sortByDesc('month_index')->first();
            $lastPaidMonth = $lastPaidPayment
                ? $startDate->copy()->addMonths($lastPaidPayment->month_index - 1)->format('d-m-Y')
                : '-';

            $isCompleted = $d->months_remaining <= 0;
            $isOnTrack   = !$isCompleted && $payment && $payment->is_paid;

            return [
                'id'              => $d->id,
                'person'          => $d->person,
                'name'            => $d->name,
                'months_remaining'=> $d->months_remaining,
                'month'           => $lastPaidMonth,
                'amount'          => $d->payment_per_month,
                'amount_remaining'=> $d->amount_remaining,
                'is_paid'         => $payment ? $payment->is_paid : false,
                'completed'       => $isCompleted,
                'on_track'        => $isOnTrack,
            ];
        });

        $otherDebtThisMonthTotal = $otherDebtsThisMonth
            ->filter(fn($d) => !$d['completed'] && !$d['is_paid'])
            ->sum('amount');

        // --- Pie chart data ---
        $salary = (float) $user->monthly_salary;
        $pieLabels = ['Net Salary', 'Total Monthly Commitments', 'Total Short-Term Debt'];
        $total = $salary + $adjustedCommitmentPerMonth + $totalDebtPerMonth + $totalOtherDebtPerMonth;
        $pieValues = $total > 0 ? [
            round($salary / $total * 100, 1),
            round($adjustedCommitmentPerMonth / $total * 100, 1),
            round($totalDebtPerMonth / $total * 100, 1),
        ] : [33.3, 33.3, 33.3];

        return view('dashboard', compact(
            'user', 'salary', 'totalCommitmentPerMonth', 'adjustedCommitmentPerMonth', 'totalDebtPerMonth', 'totalOtherDebtPerMonth',
            'commitmentsThisMonth', 'debtsThisMonth', 'otherDebtsThisMonth',
            'myDebtThisMonthTotal', 'otherDebtThisMonthTotal',
            'pieLabels', 'pieValues', 'otherDebts'
        ));
    }

    public function updateSalary(Request $request)
    {
        $request->validate(['monthly_salary' => 'required|numeric|min:0']);
        auth()->user()->update(['monthly_salary' => $request->monthly_salary]);
        return redirect()->route('dashboard')->with('success', 'Salary updated.');
    }
}
