<?php

namespace App\Http\Controllers;

use App\Models\OtherDebt;
use App\Models\OtherDebtPayment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OtherDebtController extends Controller
{
    public function index()
    {
        $otherDebts = auth()->user()->otherDebts()->with('payments')->latest()->get();
        return view('other-debts.index', compact('otherDebts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person'       => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0.01',
            'months'       => 'required|integer|min:1',
            'start_month'  => 'required|date',
        ]);

        $debt = auth()->user()->otherDebts()->create([
            'person'       => $validated['person'],
            'name'         => $validated['name'],
            'total_amount' => $validated['total_amount'],
            'months'       => $validated['months'],
            'start_month'  => $validated['start_month'],
        ]);

        for ($i = 1; $i <= $debt->months; $i++) {
            OtherDebtPayment::create(['other_debt_id' => $debt->id, 'month_index' => $i, 'is_paid' => false]);
        }

        return redirect()->route('other-debts.index')->with('success', 'Debt added successfully.');
    }

    public function togglePayment(Request $request, OtherDebt $otherDebt, int $monthIndex)
    {
        abort_unless(auth()->id() === $otherDebt->user_id, 403);
        $payment = $otherDebt->payments()->where('month_index', $monthIndex)->firstOrFail();
        $payment->update(['is_paid' => !$payment->is_paid]);

        $otherDebt->load('payments');

        $startDate         = Carbon::parse($otherDebt->start_month);
        $currentMonthIndex = (int) $startDate->diffInMonths(now()) + 1;
        $currentPayment    = $otherDebt->payments->firstWhere('month_index', $currentMonthIndex);
        $isCompleted       = $otherDebt->paid_months_count >= $otherDebt->months;
        $isOnTrack         = !$isCompleted && $currentPayment && $currentPayment->is_paid;

        return response()->json([
            'is_paid'          => $payment->is_paid,
            'paid_months'      => $otherDebt->paid_months_count,
            'total_months'     => $otherDebt->months,
            'months_remaining' => $otherDebt->months_remaining,
            'amount_paid'      => number_format($otherDebt->amount_paid, 2),
            'amount_remaining' => number_format($otherDebt->amount_remaining, 2),
            'progress'         => $otherDebt->months > 0 ? round(($otherDebt->paid_months_count / $otherDebt->months) * 100, 1) : 0,
            'is_completed'     => $isCompleted,
            'is_on_track'      => $isOnTrack,
        ]);
    }

    public function destroy(OtherDebt $otherDebt)
    {
        abort_unless(auth()->id() === $otherDebt->user_id, 403);
        $otherDebt->delete();
        return redirect()->route('other-debts.index')->with('success', 'Debt deleted.');
    }
}
