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
        $thisMonthDate = $now->startOfMonth()->toDateString();

        // --- Commitments ---
        $commitments = $user->commitments()->with('payments')->latest()->get();
        foreach ($commitments as $c) {
            $c->syncPayments();
        }
        $commitments = $user->commitments()->with('payments')->latest()->get();

        $totalCommitmentPerMonth = $commitments->sum('amount_per_month');

        $commitmentsThisMonth = $commitments->map(function ($c) use ($thisMonthDate) {
            $payment = $c->payments->firstWhere('month_date', $thisMonthDate);
            return [
                'name'      => $c->name,
                'month'     => now()->format('m-Y'),
                'amount'    => $c->amount_per_month,
                'is_paid'   => $payment ? $payment->is_paid : false,
            ];
        });

        // --- My Debts ---
        $debts = $user->debts()->with('payments')->latest()->get();

        $activeDebts = $debts->filter(fn($d) => $d->months_remaining > 0);
        $totalDebtPerMonth = $activeDebts->sum('payment_per_month');

        $debtsThisMonth = $debts->map(function ($d) use ($now) {
            $startDate = Carbon::parse($d->start_month);
            $monthIndex = $startDate->diffInMonths($now->copy()->startOfMonth()) + 1;
            $payment = null;
            if ($monthIndex >= 1 && $monthIndex <= $d->months) {
                $payment = $d->payments->firstWhere('month_index', $monthIndex);
            }
            $amountLeft = $d->amount_remaining;
            return [
                'name'            => $d->name,
                'months_remaining'=> $d->months_remaining,
                'month'           => $now->format('m-Y'),
                'amount'          => $d->payment_per_month,
                'amount_remaining'=> $amountLeft,
                'is_paid'         => $payment ? $payment->is_paid : false,
                'completed'       => $d->months_remaining <= 0,
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
            $monthIndex = $startDate->diffInMonths($now->copy()->startOfMonth()) + 1;
            $payment = null;
            if ($monthIndex >= 1 && $monthIndex <= $d->months) {
                $payment = $d->payments->firstWhere('month_index', $monthIndex);
            }
            return [
                'id'              => $d->id,
                'person'          => $d->person,
                'name'            => $d->name,
                'months_remaining'=> $d->months_remaining,
                'month'           => $now->format('m-Y'),
                'amount'          => $d->payment_per_month,
                'amount_remaining'=> $d->amount_remaining,
                'is_paid'         => $payment ? $payment->is_paid : false,
                'completed'       => $d->months_remaining <= 0,
            ];
        });

        $otherDebtThisMonthTotal = $otherDebtsThisMonth
            ->filter(fn($d) => !$d['completed'] && !$d['is_paid'])
            ->sum('amount');

        // --- Pie chart data ---
        $salary = (float) $user->monthly_salary;
        $pieLabels = ['Net Salary', 'Total Monthly Commitments', 'Total Short-Term Debt'];
        $total = $salary + $totalCommitmentPerMonth + $totalDebtPerMonth + $totalOtherDebtPerMonth;
        $pieValues = $total > 0 ? [
            round($salary / $total * 100, 1),
            round($totalCommitmentPerMonth / $total * 100, 1),
            round(($totalDebtPerMonth + $totalOtherDebtPerMonth) / $total * 100, 1),
        ] : [33.3, 33.3, 33.3];

        return view('dashboard', compact(
            'user', 'salary', 'totalCommitmentPerMonth', 'totalDebtPerMonth', 'totalOtherDebtPerMonth',
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
