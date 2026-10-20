<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function show($slug)
    {
        if (str_starts_with($slug, 'demo-')) {
            return response()->json([
                'id' => 9999,
                'bankAccounts' => [],
                'gifts' => [],
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
            ]);
        }

        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $payment = Payment::where('wedding_id', $wedding->id)->firstOrFail();

        return response()->json($this->formatPayment($payment));
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $payment = Payment::where('wedding_id', $wedding->id)->firstOrFail();

        $payment->update([
            'bank_accounts' => $request->input('bankAccounts', []),
            'gifts' => $request->input('gifts', []),
        ]);

        return response()->json($this->formatPayment($payment));
    }

    private function formatPayment(Payment $payment)
    {
        return [
            'id' => $payment->id,
            'bankAccounts' => $payment->bank_accounts ?? [],
            'gifts' => $payment->gifts ?? [],
            'createdAt' => $payment->created_at ? $payment->created_at->toIso8601String() : null,
            'updatedAt' => $payment->updated_at ? $payment->updated_at->toIso8601String() : null,
        ];
    }
}
