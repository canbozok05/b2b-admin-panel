import { Head, Link } from '@inertiajs/react';
import orders from '@/routes/orders';

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    status: string;
    created_at: string;
    customer: { id: number; name: string } | null;
};

type Props = {
    orders: Order[];
};

const statusLabels: Record<string, string> = {
    pending: 'Bekliyor',
    confirmed: 'Onaylandı',
    shipped: 'Kargoya Verildi',
    completed: 'Tamamlandı',
    cancelled: 'İptal',
};

export default function OrderIndex({ orders: allOrders }: Props) {
    return (
        <>
            <Head title="Siparişler" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Siparişler</h1>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">Sipariş No</th>
                            <th className="p-2">Müşteri</th>
                            <th className="p-2">Tutar</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allOrders.map((order) => (
                            <tr
                                key={order.id}
                                className={`border-b ${order.status === 'pending' ? 'bg-yellow-500/10 font-medium' : ''}`}
                            >
                                <td className="p-2">
                                    <Link
                                        href={orders.show.url(order.id)}
                                        className="text-primary underline"
                                    >
                                        {order.order_number}
                                    </Link>
                                </td>
                                <td className="p-2">
                                    {order.customer ? order.customer.name : '—'}
                                </td>
                                <td className="p-2">{order.total_amount}</td>
                                <td className="p-2">
                                    {statusLabels[order.status] ?? order.status}
                                </td>
                                <td className="p-2">
                                    {new Date(
                                        order.created_at,
                                    ).toLocaleDateString('tr-TR')}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
