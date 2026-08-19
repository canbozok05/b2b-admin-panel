<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Satış Raporu</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
        }
        h1 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .summary-table td {
            border: 1px solid #ddd;
            padding: 10px;
            width: 50%;
            vertical-align: top;
        }
        .month-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .stat {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-label {
            color: #666;
            font-size: 9px;
            text-transform: uppercase;
        }
        h2 {
            font-size: 14px;
            margin-top: 24px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.data-table th {
            background: #f3f3f3;
            text-align: left;
            padding: 5px 6px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
        }
        table.data-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .empty {
            color: #999;
            font-style: italic;
            padding: 8px 0;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    @php
        $statusLabels = [
            'pending' => 'Bekliyor',
            'confirmed' => 'Onaylandı',
            'shipped' => 'Kargoya Verildi',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal',
        ];
    @endphp

    <h1>Satış Raporu</h1>
    <p class="subtitle">Oluşturulma: {{ $generatedAt->format('d.m.Y H:i') }}</p>

    <table class="summary-table">
        <tr>
            <td>
                <div class="month-name">{{ $currentMonth['label'] }}</div>
                <div class="stat">{{ number_format($currentMonth['total_sales'], 2, ',', '.') }} ₺</div>
                <div class="stat-label">Toplam Satış ({{ $currentMonth['order_count'] }} sipariş)</div>
            </td>
            <td>
                <div class="month-name">{{ $previousMonth['label'] }}</div>
                <div class="stat">{{ number_format($previousMonth['total_sales'], 2, ',', '.') }} ₺</div>
                <div class="stat-label">Toplam Satış ({{ $previousMonth['order_count'] }} sipariş)</div>
            </td>
        </tr>
    </table>

    @foreach ([$currentMonth, $previousMonth] as $index => $month)
        <div @if ($index === 1) class="page-break" @endif>
            <h2>{{ $month['label'] }} — Siparişler ({{ $month['range'] }})</h2>

            @if ($month['orders']->isEmpty())
                <p class="empty">Bu dönemde sipariş bulunmuyor.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th class="text-right">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($month['orders'] as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer->name ?? '—' }}</td>
                                <td>{{ $order->created_at->format('d.m.Y') }}</td>
                                <td>{{ $statusLabels[$order->status] ?? $order->status }}</td>
                                <td class="text-right">{{ number_format((float) $order->total_amount, 2, ',', '.') }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <h2>{{ $month['label'] }} — En Çok Satan Ürünler</h2>

            @if ($month['top_products']->isEmpty())
                <p class="empty">Bu dönemde satılan ürün bulunmuyor.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th class="text-right">Adet</th>
                            <th class="text-right">Gelir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($month['top_products'] as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td class="text-right">{{ $product->total_quantity }}</td>
                                <td class="text-right">{{ number_format((float) $product->total_revenue, 2, ',', '.') }} ₺</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
