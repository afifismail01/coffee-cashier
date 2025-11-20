<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionDetailController extends Controller
{
    // Get all transaction details for a specific transcation
    public function index($transactionId)
    {
        $transaction = Transaction::with('details.product')->findOrFail($transactionId);

        return response()->json(['message' => 'Transaction details fetched successfully', 'data' => $transaction], 200);
    }

    // Add new item to an existing transaction
    public function store(Request $request, $transactionId)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $transaction = Transaction::findOrFail($transactionId);

            $product = Product::findOrFail($validated['product_id']);
            $subtotal = $product->price * $validated['quantity'];

            // Insert new detail
            $detail = TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'subtotal' => $subtotal,
            ]);

            // Update stock
            $product->decrement('stock', $validated['quantity']);

            // Recalculate totals
            $this->recalculateTransaction($transaction);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Item added successfully',
                    'data' => $detail,
                ],
                201,
            );
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    // Update quantity of transaction detail
    public function update(Request $request, $detailId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $detail = TransactionDetail::findOrFail($detailId);
            $product = Product::findOrFail($detail->product_id);
            $transaction = Transaction::findOrFail($detail->transaction_id);

            // Return old stock
            $product->increment('stock', $detail->quantity);

            // Update new subtotal
            $detail->quantity = $validated['quantity'];
            $detail->subtotal = $validated['quantity'] * $product->price;
            $detail->save();

            // Kurangi stock sesuai quantity baru
            $product->decrement('stock', $validated['quantity']);

            // Recalculate totals
            $this->recalculateTransaction($transaction);

            DB::commit();

            return response()->json(['message' => 'Item updated successfully', 'data' => $detail], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    // Delete transaction detail
    public function destroy($detailId)
    {
        DB::beginTransaction();
        try {
            $detail = TransactionDetail::findOrFail($detailId);
            $transaction = Transaction::findOrFail($detail->transaction_id);

            // Return stock
            $product = Product::findOrFail($detail->product_id);
            $product->increment('stock', $detail->quantity);

            // delete detail
            $detail->delete();

            // Recalculate transaction
            $this->recalculateTransaction($transaction);

            DB::commit();

            return response()->json(['message' => 'Item deleted successfully'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    // Recalculate totals and change amount after detail update

    private function recalculateTransaction(Transaction $transaction)
    {
        $newTotal = $transaction->details()->sum('subtotal');
        $transaction->total_amount = $newTotal;

        // Payment logic
        if ($transaction->payment_method === PaymentMethodEnum::CASH->value) {
            if ($transaction->payment_method !== null) {
                if ($transaction->payment_method >= $newTotal) {
                    $transaction->change_amount = $transaction->payment_amount - $newTotal;
                    $transaction->status = PaymentStatusEnum::PAID->value;
                } else {
                    $transaction->change_amount = null;
                    $transaction->status = PaymentStatusEnum::PENDING->value;
                }
            } else {
                $transaction->change_amount = null;
                $transaction->status = PaymentStatusEnum::PENDING->value;
            }
        } else {
            $transaction->change_amount = null;
        }
        $transaction->save();
    }
}
