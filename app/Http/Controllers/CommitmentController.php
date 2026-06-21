<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\CommitmentPayment;
use Illuminate\Http\Request;

class CommitmentController extends Controller
{
    public function index()
    {
        $commitments = auth()->user()->commitments()->with('payments')->latest()->get();

        // Sync payment rows up to current month for each commitment
        foreach ($commitments as $commitment) {
            $commitment->syncPayments();
        }

        // Reload with fresh payment data
        $commitments = auth()->user()->commitments()->with('payments')->latest()->get();

        return view('commitments.index', compact('commitments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'amount_per_month' => 'required|numeric|min:0.01',
            'date_started'     => 'required|date',
        ]);

        $commitment = auth()->user()->commitments()->create($validated);
        $commitment->syncPayments();

        return redirect()->route('commitments.index')->with('success', 'Commitment added successfully.');
    }

    public function togglePayment(Request $request, Commitment $commitment, CommitmentPayment $payment)
    {
        abort_unless(auth()->id() === $commitment->user_id, 403);

        $payment->update(['is_paid' => !$payment->is_paid]);

        $commitment->load('payments');

        return response()->json([
            'is_paid'          => $payment->is_paid,
            'paid_months'      => $commitment->paid_months_count,
            'total_months'     => $commitment->total_months,
            'months_remaining' => $commitment->months_remaining,
            'amount_paid'      => number_format($commitment->amount_paid, 2),
            'amount_remaining' => number_format($commitment->amount_remaining, 2),
            'progress'         => $commitment->total_months > 0
                ? round(($commitment->paid_months_count / $commitment->total_months) * 100, 1)
                : 0,
        ]);
    }

    public function destroy(Commitment $commitment)
    {
        abort_unless(auth()->id() === $commitment->user_id, 403);
        $commitment->delete();
        return redirect()->route('commitments.index')->with('success', 'Commitment deleted.');
    }
}
