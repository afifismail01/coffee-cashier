<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionDetail;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    public function dailyReport(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['message' => 'Parameter date is required'], 422);
        }

        // Data per product
        $report = TransactionDetail::select('products.id as product_id', 'products.name as product_name', DB::raw('SUM(transaction_details.quantity) as total_quantity'), DB::raw('SUM(transaction_details.subtotal) as total_revenue'))->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')->join('products', 'products.id', '=', 'transaction_details.product_id')->whereDate('transaction_details.created_at', $date)->groupBy('products.id', 'products.name')->orderBy('products.name')->get();

        // Summary
        $summary = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity) as total_products_sold'), DB::raw('SUM(transaction_details.subtotal) as total_revenue'))->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')->whereDate('transactions.created_at', $date)->first();

        return response()->json(
            [
                'message' => 'Daily report generated',
                'data' => $date,
                'data' => $report,
                'summary' => $summary,
            ],
            200,
        );
    }

    public function monthlyReport(Request $request)
    {
        // Take params from month(YYYY-MM), default is the current month
        $month = $request->input('month', now()->format('Y-m'));

        // Monthly report query
        $report = Transaction::selectRaw(
            "
        WEEK(transactions.created_at,1) AS week_number,
        SUM(transaction_details.quantity) AS total_cups,
        SUM(transaction_details.subtotal) AS total_revenue",
        )
            ->join('transaction_details', 'transaction_id', '=', 'transaction_details.transaction_id')
            ->whereRaw("DATE_FORMAT(transactions.created_at,'%Y-%m') = ?", [$month])
            ->groupBy('week_number')
            ->orderBy('week_number')
            ->get();

        // Calculate the grand total
        $total = [
            'total_cups' => $report->sum('total_cups'),
            'total_revenue' => $report->sum('total_revenue'),
        ];

        return response()->json(
            [
                'message' => 'Monthly report retrieved successfully',
                'month' => $month,
                'data' => $report,
                'summary' => $total,
            ],
            200,
        );
    }
}
