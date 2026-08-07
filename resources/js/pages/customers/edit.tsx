import { Head, useForm } from '@inertiajs/react';
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
    customer: Customer;
};

export default function CustomerEdit({ customer }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: customer.name,
        email: customer.email,
        phone: customer.phone ?? '',
        address: customer.address ?? '',
        status: customer.status,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(customers.update.url(customer.id));
    }

    return (
        <>
            <Head title="Müşteri Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Müşteri Düzenle</h1>

                <form
                    onSubmit={handleSubmit}
                    className="flex max-w-md flex-col gap-4"
                >
                    <div>
                        <label className="text-sm font-medium">İsim</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.name && (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Email</label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.email && (
                            <p className="text-sm text-destructive">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Telefon</label>
                        <input
                            type="text"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.phone && (
                            <p className="text-sm text-destructive">
                                {errors.phone}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Adres</label>
                        <textarea
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.address && (
                            <p className="text-sm text-destructive">
                                {errors.address}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Durum</label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="w-full rounded-md border p-2"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                        {errors.status && (
                            <p className="text-sm text-destructive">
                                {errors.status}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-primary p-2 text-primary-foreground"
                    >
                        Güncelle
                    </button>
                </form>
            </div>
        </>
    );
}
