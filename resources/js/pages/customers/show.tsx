import { Head, Link } from '@inertiajs/react';
import customers from '@/routes/customers';
import orders from '@/routes/orders';

type Address = {
    id: number;
    label: string;
    address: string;
};

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    addresses: Address[];
};

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    status: string;
    created_at: string;
    customer_address: { id: number; label: string; address: string } | null;
};

type Props = {
    customer: Customer;
    orders: Order[];
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

export default function CustomerShow({
    customer,
    orders: customerOrders,
}: Props) {
    const totalSpent = customerOrders
        .filter((order) => !['pending', 'cancelled'].includes(order.status))
        .reduce((sum, order) => sum + Number(order.total_amount), 0);

    return (
        <>
            <Head title={customer.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{customer.name}</h1>
                    <Link
                        href={customers.edit.url(customer.id)}
                        className="rounded-md border px-4 py-2 text-sm"
                    >
                        Düzenle
                    </Link>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    <div className="rounded-xl border bg-card p-5 md:col-span-1">
                        <h2 className="mb-3 text-lg font-semibold">
                            Müşteri Bilgisi
                        </h2>
                        <div className="flex flex-col gap-1 text-sm">
                            <p className="text-muted-foreground">
                                {customer.email}
                            </p>
                            <p className="text-muted-foreground">
                                {customer.phone ?? '—'}
                            </p>
                            <p className="mt-1">
                                {customer.status === 'active'
                                    ? 'Aktif'
                                    : 'Pasif'}
                            </p>
                        </div>

                        <h3 className="mt-4 mb-1 text-sm font-semibold">
                            Adresler
                        </h3>
                        {customer.addresses.length > 0 ? (
                            <ul className="flex flex-col gap-1 text-sm text-muted-foreground">
                                {customer.addresses.map((address) => (
                                    <li key={address.id}>
                                        <span className="font-medium text-foreground">
                                            {address.label}
                                        </span>{' '}
                                        — {address.address}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Kayıtlı adresi yok.
                            </p>
                        )}
                    </div>

                    <div className="rounded-xl border bg-card p-5 md:col-span-2">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">
                                Sipariş Geçmişi
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Toplam harcama: {formatTl(totalSpent)} ₺
                            </p>
                        </div>

                        {customerOrders.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Bu müşterinin henüz siparişi yok.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="p-2">Sipariş No</th>
                                        <th className="p-2">Tarih</th>
                                        <th className="p-2">Teslimat Adresi</th>
                                        <th className="p-2">Durum</th>
                                        <th className="p-2">Tutar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {customerOrders.map((order) => (
                                        <tr key={order.id} className="border-b">
                                            <td className="p-2">
                                                <Link
                                                    href={orders.show.url(
                                                        order.id,
                                                    )}
                                                    className="text-primary underline"
                                                >
                                                    {order.order_number}
                                                </Link>
                                            </td>
                                            <td className="p-2">
                                                {new Date(
                                                    order.created_at,
                                                ).toLocaleDateString('tr-TR')}
                                            </td>
                                            <td className="p-2">
                                                {order.customer_address
                                                    ? order.customer_address
                                                          .label
                                                    : '—'}
                                            </td>
                                            <td className="p-2">
                                                {statusLabels[order.status] ??
                                                    order.status}
                                            </td>
                                            <td className="p-2">
                                                {formatTl(
                                                    Number(order.total_amount),
                                                )}{' '}
                                                ₺
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
