import { Head } from '@inertiajs/react';
import reports from '@/routes/reports';

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    status: string;
    created_at: string;
    customer: { id: number; name: string } | null;
};

type TopProduct = {
    name: string;
    total_quantity: number;
    total_revenue: number;
};

type MonthlyReport = {
    label: string;
    range: string;
    total_sales: number;
    order_count: number;
    orders: Order[];
    top_products: TopProduct[];
};

type Props = {
    currentMonth: MonthlyReport;
    previousMonth: MonthlyReport;
};

const statusLabels: Record<string, string> = {
    pending: 'Bekliyor',
    confirmed: 'Onaylandı',
    shipped: 'Kargoya Verildi',
    completed: 'Tamamlandı',
    cancelled: 'İptal',
};

function formatTl(value: number): string {
    return value.toLocaleString('tr-TR', { minimumFractionDigits: 2 });
}

function MonthSection({ month }: { month: MonthlyReport }) {
    return (
        <div className="flex flex-col gap-4">
            <div className="rounded-xl border bg-card p-5 shadow-sm">
                <p className="text-sm text-muted-foreground">{month.label}</p>
                <p className="mt-2 text-3xl font-bold">
                    {formatTl(month.total_sales)} ₺
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {month.range} arası, {month.order_count} sipariş
                </p>
            </div>

            <div className="rounded-xl border bg-card p-5">
                <h3 className="mb-3 text-sm font-semibold">Siparişler</h3>
                {month.orders.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Bu dönemde sipariş bulunmuyor.
                    </p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="p-2">Sipariş No</th>
                                <th className="p-2">Müşteri</th>
                                <th className="p-2">Tarih</th>
                                <th className="p-2">Durum</th>
                                <th className="p-2">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {month.orders.map((order) => (
                                <tr key={order.id} className="border-b">
                                    <td className="p-2">
                                        {order.order_number}
                                    </td>
                                    <td className="p-2">
                                        {order.customer?.name ?? '—'}
                                    </td>
                                    <td className="p-2">
                                        {new Date(
                                            order.created_at,
                                        ).toLocaleDateString('tr-TR')}
                                    </td>
                                    <td className="p-2">
                                        {statusLabels[order.status] ??
                                            order.status}
                                    </td>
                                    <td className="p-2">
                                        {formatTl(Number(order.total_amount))} ₺
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <div className="rounded-xl border bg-card p-5">
                <h3 className="mb-3 text-sm font-semibold">
                    En Çok Satan Ürünler
                </h3>
                {month.top_products.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Bu dönemde satılan ürün bulunmuyor.
                    </p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="p-2">Ürün</th>
                                <th className="p-2">Adet</th>
                                <th className="p-2">Gelir</th>
                            </tr>
                        </thead>
                        <tbody>
                            {month.top_products.map((product) => (
                                <tr key={product.name} className="border-b">
                                    <td className="p-2">{product.name}</td>
                                    <td className="p-2">
                                        {product.total_quantity}
                                    </td>
                                    <td className="p-2">
                                        {formatTl(product.total_revenue)} ₺
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}

export default function ReportsIndex({ currentMonth, previousMonth }: Props) {
    return (
        <>
            <Head title="Satış Raporu" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Satış Raporu</h1>
                    <a
                        href={reports.sales.pdf.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        PDF İndir
                    </a>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <MonthSection month={currentMonth} />
                    <MonthSection month={previousMonth} />
                </div>
            </div>
        </>
    );
}
