import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import orders from '@/routes/orders';
import type { Auth } from '@/types/auth';

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    status: string;
    created_at: string;
    customer: { id: number; name: string } | null;
};

type Filters = {
    search: string;
    status: string;
};

type Props = {
    orders: Order[];
    filters: Filters;
};

const statusLabels: Record<string, string> = {
    pending: 'Bekliyor',
    confirmed: 'Onaylandı',
    shipped: 'Kargoya Verildi',
    completed: 'Tamamlandı',
    cancelled: 'İptal',
};

export default function OrderIndex({ orders: allOrders, filters }: Props) {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const isSuperAdmin = (auth.roles ?? []).includes('Süper Admin');

    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                orders.index.url(),
                { search: search || undefined, status: status || undefined },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status]);

    return (
        <>
            <Head title="Siparişler" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Siparişler</h1>
                    {isSuperAdmin && (
                        <Link
                            href={orders.create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                        >
                            Yeni Sipariş
                        </Link>
                    )}
                </div>

                <div className="flex gap-3">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Sipariş no veya müşteri adı ara..."
                        className="w-full max-w-sm rounded-md border p-2 text-sm"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border p-2 text-sm"
                    >
                        <option value="">Tüm Durumlar</option>
                        {Object.entries(statusLabels).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">Sipariş No</th>
                            <th className="p-2">Müşteri</th>
                            <th className="p-2">Tutar</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">Tarih</th>
                            {isSuperAdmin && <th className="p-2">İşlemler</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {allOrders.length === 0 && (
                            <tr>
                                <td
                                    colSpan={isSuperAdmin ? 6 : 5}
                                    className="p-4 text-center text-muted-foreground"
                                >
                                    Filtreyle eşleşen sipariş bulunamadı.
                                </td>
                            </tr>
                        )}
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
                                <td className="p-2">
                                    {Number(order.total_amount).toLocaleString(
                                        'tr-TR',
                                        { minimumFractionDigits: 2 },
                                    )}{' '}
                                    ₺
                                </td>
                                <td className="p-2">
                                    {statusLabels[order.status] ?? order.status}
                                </td>
                                <td className="p-2">
                                    {new Date(
                                        order.created_at,
                                    ).toLocaleDateString('tr-TR')}
                                </td>
                                {isSuperAdmin && (
                                    <td className="p-2">
                                        <div className="flex gap-3">
                                            <Link
                                                href={orders.edit.url(order.id)}
                                                className="text-primary underline"
                                            >
                                                Düzenle
                                            </Link>
                                            <Link
                                                href={orders.destroy.url(
                                                    order.id,
                                                )}
                                                method="delete"
                                                as="button"
                                                onClick={(e) => {
                                                    if (
                                                        !confirm(
                                                            'Bu siparişi silmek istediğine emin misin? Ürünlerin stoku geri eklenecek.',
                                                        )
                                                    ) {
                                                        e.preventDefault();
                                                    }
                                                }}
                                                className="text-destructive underline"
                                            >
                                                Sil
                                            </Link>
                                        </div>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
