import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import customers from '@/routes/customers';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
};

type Filters = {
    search: string;
    status: string;
};

type Props = {
    customers: Customer[];
    filters: Filters;
};

export default function CustomerIndex({
    customers: allCustomers,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                customers.index.url(),
                { search: search || undefined, status: status || undefined },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status]);

    return (
        <>
            <Head title="Müşteriler" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Müşteriler</h1>
                    <Link
                        href={customers.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni Müşteri
                    </Link>
                </div>

                <div className="flex gap-3">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="İsim, email veya telefon ara..."
                        className="w-full max-w-sm rounded-md border p-2 text-sm"
                    />
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="rounded-md border p-2 text-sm"
                    >
                        <option value="">Tüm Durumlar</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                    </select>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">İsim</th>
                            <th className="p-2">Email</th>
                            <th className="p-2">Telefon</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allCustomers.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-4 text-center text-muted-foreground"
                                >
                                    Filtreyle eşleşen müşteri bulunamadı.
                                </td>
                            </tr>
                        )}
                        {allCustomers.map((customer) => (
                            <tr key={customer.id} className="border-b">
                                <td className="p-2">
                                    <Link
                                        href={customers.show.url(customer.id)}
                                        className="text-primary underline"
                                    >
                                        {customer.name}
                                    </Link>
                                </td>
                                <td className="p-2">{customer.email}</td>
                                <td className="p-2">{customer.phone ?? '—'}</td>
                                <td className="p-2">
                                    {customer.status === 'active'
                                        ? 'Aktif'
                                        : 'Pasif'}
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={customers.edit.url(
                                                customer.id,
                                            )}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        <Link
                                            href={customers.destroy.url(
                                                customer.id,
                                            )}
                                            method="delete"
                                            as="button"
                                            onClick={(e) => {
                                                if (
                                                    !confirm(
                                                        'Bu müşteriyi silmek istediğine emin misin?',
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
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
