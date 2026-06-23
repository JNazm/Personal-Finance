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
            'type'             => 'required|in:infinite,normal,normal-month',
            'end_date'         => 'nullable|date|required_if:type,normal|after:date_started',
            'num_months'       => 'nullable|integer|min:1|required_if:type,normal-month',
        ]);

        // For normal-month, compute end_date from num_months
        if ($validated['type'] === 'normal-month') {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['date_started'])
                ->addMonths((int) $validated['num_months'] - 1)
                ->format('Y-m-d');
        }

        $commitment = auth()->user()->commitments()->create([
            'name'             => $validated['name'],
            'amount_per_month' => $validated['amount_per_month'],
            'date_started'     => $validated['date_started'],
            'type'             => $validated['type'],
            'end_date'         => $validated['end_date'] ?? null,
        ]);
        $commitment->syncPayments();

        return redirect()->route('commitments.index')->with('success', 'Commitment added successfully.');
    }

    public function togglePayment(Request $request, Commitment $commitment, CommitmentPayment $payment)
    {
        abort_unless(auth()->id() === $commitment->user_id, 403);

        $payment->update(['is_paid' => !$payment->is_paid]);

        $commitment->load('payments');

        $thisYearMonth  = now()->format('Y-m');
        $currentPayment = $commitment->payments->first(fn($p) => str_starts_with($p->month_date, $thisYearMonth));
        $isCompleted    = in_array($commitment->type, ['normal', 'normal-month']) && $commitment->end_date
                          && now()->gt($commitment->end_date)
                          && $commitment->months_remaining === 0;
        $paymentNotDueYet = $currentPayment && $currentPayment->month_date > now()->format('Y-m-d');
        $isOnTrack      = !$isCompleted && ($paymentNotDueYet || ($currentPayment && $currentPayment->is_paid));

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
            'is_completed'     => $isCompleted,
            'is_on_track'      => $isOnTrack,
        ]);
    }

    public function toggleYear(Request $request, Commitment $commitment)
    {
        abort_unless(auth()->id() === $commitment->user_id, 403);
        $request->validate(['year' => 'required|integer', 'is_paid' => 'required|boolean']);

        $commitment->payments()
            ->whereRaw("strftime('%Y', month_date) = ?", [(string) $request->year])
            ->update(['is_paid' => $request->is_paid]);

        $commitment->load('payments');

        $thisYearMonth  = now()->format('Y-m');
        $currentPayment = $commitment->payments->first(fn($p) => str_starts_with($p->month_date, $thisYearMonth));
        $isCompleted    = in_array($commitment->type, ['normal', 'normal-month']) && $commitment->end_date
                          && now()->gt($commitment->end_date)
                          && $commitment->months_remaining === 0;
        $paymentNotDueYet = $currentPayment && $currentPayment->month_date > now()->format('Y-m-d');
        $isOnTrack      = !$isCompleted && ($paymentNotDueYet || ($currentPayment && $currentPayment->is_paid));

        return response()->json([
            'paid_months'      => $commitment->paid_months_count,
            'total_months'     => $commitment->total_months,
            'months_remaining' => $commitment->months_remaining,
            'amount_paid'      => number_format($commitment->amount_paid, 2),
            'amount_remaining' => number_format($commitment->amount_remaining, 2),
            'progress'         => $commitment->total_months > 0
                ? round(($commitment->paid_months_count / $commitment->total_months) * 100, 1) : 0,
            'is_completed'     => $isCompleted,
            'is_on_track'      => $isOnTrack,
        ]);
    }

    public function destroy(Commitment $commitment)
    {
        abort_unless(auth()->id() === $commitment->user_id, 403);
        $commitment->delete();
        return redirect()->route('commitments.index')->with('success', 'Commitment deleted.');
    }
}
