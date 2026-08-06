import { Head, useForm } from '@inertiajs/react';
import orders from '@/routes/orders';

type OrderItem = {
    id: number;
    quantity: number;
    unit_price: string;
    product: { id: number; name: string; sku: string } | null;
};

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    status: string;
    customer: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        address: string | null;
    } | null;
    order_items: OrderItem[];
};

type Props = {
    order: Order;
};

const statusLabels: Record<string, string> = {
    pending: 'Bekliyor',
    confirmed: 'Onaylandı',
    shipped: 'Kargoya Verildi',
    completed: 'Tamamlandı',
    cancelled: 'İptal',
};

export default function OrderShow({ order }: Props) {
    const { data, setData, patch, processing } = useForm({
        status: order.status,
    });

    function handleStatusSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(orders.updateStatus.url(order.id));
    }

    return (
        <>
            <Head title={`Sipariş ${order.order_number}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">
                    Sipariş {order.order_number}
                </h1>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="rounded-xl border bg-card p-5">
                        <h2 className="mb-3 text-lg font-semibold">Müşteri</h2>
                        {order.customer ? (
                            <div className="flex flex-col gap-1 text-sm">
                                <p>{order.customer.name}</p>
                                <p className="text-muted-foreground">
                                    {order.customer.email}
                                </p>
                                <p className="text-muted-foreground">
                                    {order.customer.phone ?? '—'}
                                </p>
                                <p className="text-muted-foreground">
                                    {order.customer.address ?? '—'}
                                </p>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Müşteri bulunamadı
                            </p>
                        )}
                    </div>

                    <div className="rounded-xl border bg-card p-5">
                        <h2 className="mb-3 text-lg font-semibold">Durum</h2>
                        <form
                            onSubmit={handleStatusSubmit}
                            className="flex flex-col gap-3"
                        >
                            <select
                                value={data.status}
                                onChange={(e) =>
                                    setData('status', e.target.value)
                                }
                                className="w-full rounded-md border p-2"
                            >
                                {Object.entries(statusLabels).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-primary p-2 text-sm text-primary-foreground"
                            >
                                Durumu Güncelle
                            </button>
                        </form>
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-5">
                    <h2 className="mb-3 text-lg font-semibold">Ürünler</h2>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="p-2">Ürün</th>
                                <th className="p-2">SKU</th>
                                <th className="p-2">Adet</th>
                                <th className="p-2">Birim Fiyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {order.order_items.map((item) => (
                                <tr key={item.id} className="border-b">
                                    <td className="p-2">
                                        {item.product ? item.product.name : '—'}
                                    </td>
                                    <td className="p-2">
                                        {item.product ? item.product.sku : '—'}
                                    </td>
                                    <td className="p-2">{item.quantity}</td>
                                    <td className="p-2">{item.unit_price}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <p className="mt-4 text-right text-lg font-semibold">
                        Toplam: {order.total_amount}
                    </p>
                </div>
            </div>
        </>
    );
}
