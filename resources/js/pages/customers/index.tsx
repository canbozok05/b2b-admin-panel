import { Head, Link } from '@inertiajs/react';
import customers from '@/routes/customers';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    status: string;
};

type Props = {
    customers: Customer[];
};

export default function CustomerIndex({ customers: allCustomers }: Props) {
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
                        {allCustomers.map((customer) => (
                            <tr key={customer.id} className="border-b">
                                <td className="p-2">{customer.name}</td>
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
