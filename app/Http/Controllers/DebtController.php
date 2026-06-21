<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index()
    {
        $debts = auth()->user()->debts()->with('payments')->latest()->get();
        return view('debt-tracker.index', compact('debts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0.01',
            'months'       => 'required|integer|min:1',
            'start_month'  => 'required|date_format:Y-m',
        ]);

        $debt = auth()->user()->debts()->create([
            'name'         => $validated['name'],
            'total_amount' => $validated['total_amount'],
            'months'       => $validated['months'],
            'start_month'  => $validated['start_month'] . '-01',
        ]);

        // Create payment rows for each month
        for ($i = 1; $i <= $debt->months; $i++) {
            DebtPayment::create([
                'debt_id'     => $debt->id,
                'month_index' => $i,
                'is_paid'     => false,
            ]);
        }

        return redirect()->route('debt-tracker.index')->with('success', 'Debt added successfully.');
    }

    public function togglePayment(Request $request, Debt $debt, int $monthIndex)
    {
        abort_unless(auth()->id() === $debt->user_id, 403);

        $payment = $debt->payments()->where('month_index', $monthIndex)->firstOrFail();
        $payment->update(['is_paid' => !$payment->is_paid]);

        // Recalculate stats
        $debt->load('payments');
        return response()->json([
            'is_paid'          => $payment->is_paid,
            'paid_months'      => $debt->paid_months_count,
            'total_months'     => $debt->months,
            'months_remaining' => $debt->months_remaining,
            'amount_paid'      => number_format($debt->amount_paid, 2),
            'amount_remaining' => number_format($debt->amount_remaining, 2),
            'progress'         => $debt->months > 0 ? round(($debt->paid_months_count / $debt->months) * 100, 1) : 0,
        ]);
    }

    public function destroy(Debt $debt)
    {
        abort_unless(auth()->id() === $debt->user_id, 403);
        $debt->delete();
        return redirect()->route('debt-tracker.index')->with('success', 'Debt deleted.');
    }
}
