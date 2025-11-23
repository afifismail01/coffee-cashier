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
        FLOOR((DAY(transactions.created_at) - 1) / 7) + 1 AS week_number,
        SUM(transaction_details.quantity) AS total_cups,
        SUM(transaction_details.subtotal) AS total_revenue",
        )
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
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

    public function exportDaily(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['message' => 'Data is required'], 400);
        }

        // Query daily
        // Data per product
        $report = TransactionDetail::select('products.id as product_id', 'products.name as product_name', DB::raw('SUM(transaction_details.quantity) as total_quantity'), DB::raw('SUM(transaction_details.subtotal) as total_revenue'))->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')->join('products', 'products.id', '=', 'transaction_details.product_id')->whereDate('transaction_details.created_at', $date)->groupBy('products.id', 'products.name')->orderBy('products.name')->get();

        // Summary
        $summary = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity) as total_products_sold'), DB::raw('SUM(transaction_details.subtotal) as total_revenue'))->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')->whereDate('transaction_details.created_at', $date)->first();

        // Generate PDF from view
        $pdf = \PDF::loadView('reports.daily', [
            'report' => $report,
            'summary' => $summary,
        ]);

        return $pdf->download("Laporan-Harian-$date.pdf");
    }

    public function exportMonthly(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['message' => 'Data is required'], 400);
        }

        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));

        $report = DB::table('transactions as t')
            ->selectRaw(
                "WEEK(t.created_at,1) - WEEK(DATE_FORMAT(t.created_at,'%Y-%m-01'),1) + 1 AS week_number,
        SUM(t.total_amount) as total_income,
        SUM((SELECT SUM(quantity) FROM transaction_details WHERE transaction_id = t.id)) as total_cups",
            )
            ->whereMonth('t.created_at', $month)
            ->whereYear('t.created_at', $year)
            ->groupBy('week_number')
            ->orderBy('week_number')
            ->get();

        // Calculate the grand total
        $summary = [
            'total_cups' => $report->sum('total_cups'),
            'total_income' => $report->sum('total_income'),
            'month' => $month,
            'year' => $year,
        ];

        $pdf = \PDF::loadView('reports.monthly', [
            'report' => $report,
            'summary' => $summary,
        ]);

        return $pdf->download("Laporan-Bulanan-$year-$month.pdf");
    }
}
