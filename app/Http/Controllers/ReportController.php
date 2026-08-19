<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    /**
     * Sipariş durumları arasından fiili satış sayılmayanlar. Dashboard'daki
     * aylık satış hesaplamasıyla aynı kuralı kullanır.
     *
     * @var list<string>
     */
    private const NON_SALE_STATUSES = ['pending', 'cancelled'];

    public function salesReport(): Response
    {
        return Inertia::render('reports/index', [
            'currentMonth' => $this->monthlyReportData(now()),
            'previousMonth' => $this->monthlyReportData(now()->subMonthNoOverflow()),
        ]);
    }

    public function downloadSalesReportPdf(): SymfonyResponse
    {
        $pdf = Pdf::loadView('reports.sales-pdf', [
            'currentMonth' => $this->monthlyReportData(now()),
            'previousMonth' => $this->monthlyReportData(now()->subMonthNoOverflow()),
            'generatedAt' => now(),
        ]);

        return $pdf->download('satis-raporu-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * top_products'taki her satır name (string), total_quantity (int) ve
     * total_revenue (float) özelliklerine sahip bir stdClass'tır.
     *
     * @return array{
     *     label: string,
     *     range: string,
     *     total_sales: float,
     *     order_count: int,
     *     orders: Collection<int, Order>,
     *     top_products: BaseCollection<int, \stdClass>,
     * }
     */
    private function monthlyReportData(CarbonInterface $referenceDate): array
    {
        $start = $referenceDate->copy()->startOfMonth();
        $end = $referenceDate->copy()->endOfMonth();

        $orders = Order::with('customer')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $salesOrders = $orders->whereNotIn('status', self::NON_SALE_STATUSES);

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereNotIn('orders.status', self::NON_SALE_STATUSES)
            ->selectRaw('products.name as name, SUM(order_items.quantity) as total_quantity, SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        return [
            'label' => $referenceDate->locale('tr')->translatedFormat('F Y'),
            'range' => $start->format('d.m.Y').' - '.$end->format('d.m.Y'),
            'total_sales' => (float) $salesOrders->sum('total_amount'),
            'order_count' => $salesOrders->count(),
            'orders' => $orders,
            'top_products' => $topProducts,
        ];
    }
}
