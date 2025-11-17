<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;

class TransactionController extends Controller
{
    // Generate invoice code
    private function generateInvoiceCode()
    {
        // Date format YMD
        $today = now()->format('Ymd');

        // Find last transaction today
        $lastTransactionToday = Transaction::where('invoice_code', 'like', "INV-{$today}-%")
            ->orderBy('invoice_code', 'Desc')
            ->first();

        // Determine the serial number
        if ($lastTransactionToday) {
            // Take 4 digit from invoice
            $lastNumber = (int) substr($lastTransactionToday->invoice_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format number to 4 digit
        $numberFormatted = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return "INV-{$today}-{$numberFormatted}";
    }

    // Show all transaction
    public function index()
    {
        $transaction = Transaction::with('details.product')->get();
        return response()->json($transaction, 200);
    }

    // Show transaction by Id
    public function show($id)
    {
        $transaction = Transaction::with('details.product')->find($id);

        if (!$id) {
            return response()->json(['message' => 'Transaction not found', 404]);
        }

        return response()->json($transaction, 200);
    }

    // Store transaction data
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', Rule::in(PaymentMethodEnum::values())],
            'status' => ['required', 'string', Rule::in(PaymentStatusEnum::values())],
            'payment_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $total_amount = 0;

            // Counter
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $total_amount += $product->price * $item['quantity'];
            }

            // Payment and Change
            $payment_amount = $validated['payment_amount'] ?? null;
            $change_amount = null;

            if ($validated['payment_method'] === PaymentMethodEnum::CASH->value) {
                if ($payment_amount === null) {
                    return response()->json(['message' => 'Cash payment requires payment_amount'], 422);
                }
                if ($payment_amount < $total_amount) {
                    return response()->json(['message' => 'Payment amount is less than total amount'], 422);
                }
                // Change count
                $change_amount = $payment_amount - $total_amount;
            }

            // Insert Transaction
            $transaction = Transaction::create([
                'invoice_code' => $this->generateInvoiceCode(),
                'payment_method' => $validated['payment_method'],
                'status' => $validated['status'],
                'total_amount' => $total_amount,
                'payment_amount' => $payment_amount,
                'change_amount' => $change_amount,
            ]);

            // Insert transaction details data
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);
                // Decrease product stock
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Transaction created successfully',
                    'data' => $transaction->load('details.product'),
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create transaction', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete transaction
    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (!$id) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully'], 200);
    }
}
