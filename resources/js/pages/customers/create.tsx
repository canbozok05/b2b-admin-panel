import { Head, useForm } from '@inertiajs/react';
import customers from '@/routes/customers';

export default function CustomerCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        status: 'active',
        address_label: '',
        address_text: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(customers.store.url());
    }

    return (
        <>
            <Head title="Yeni Müşteri" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Yeni Müşteri</h1>

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
                            maxLength={255}
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
                            maxLength={255}
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
                            maxLength={20}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.phone && (
                            <p className="text-sm text-destructive">
                                {errors.phone}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-2 rounded-md border p-3">
                        <p className="text-sm font-medium">
                            İlk Adres (zorunlu)
                        </p>

                        <div>
                            <label className="text-sm font-medium">
                                Adres Etiketi
                            </label>
                            <input
                                type="text"
                                value={data.address_label}
                                onChange={(e) =>
                                    setData('address_label', e.target.value)
                                }
                                placeholder="örn. Ev, İş, Okul"
                                maxLength={100}
                                className="w-full rounded-md border p-2"
                            />
                            {errors.address_label && (
                                <p className="text-sm text-destructive">
                                    {errors.address_label}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="text-sm font-medium">Adres</label>
                            <textarea
                                value={data.address_text}
                                onChange={(e) =>
                                    setData('address_text', e.target.value)
                                }
                                maxLength={500}
                                className="w-full rounded-md border p-2"
                            />
                            {errors.address_text && (
                                <p className="text-sm text-destructive">
                                    {errors.address_text}
                                </p>
                            )}
                        </div>
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
                        Kaydet
                    </button>
                </form>
            </div>
        </>
    );
}
