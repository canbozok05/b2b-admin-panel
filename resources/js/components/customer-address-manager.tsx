import { router } from '@inertiajs/react';
import { useState } from 'react';
import customerAddresses from '@/routes/customer-addresses';

type Address = {
    id: number;
    label: string;
    address: string;
};

type Props = {
    customerId: number;
    addresses: Address[];
};

export default function CustomerAddressManager({
    customerId,
    addresses,
}: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editLabel, setEditLabel] = useState('');
    const [editAddress, setEditAddress] = useState('');

    const [newLabel, setNewLabel] = useState('');
    const [newAddress, setNewAddress] = useState('');

    function startEdit(address: Address) {
        setEditingId(address.id);
        setEditLabel(address.label);
        setEditAddress(address.address);
    }

    function cancelEdit() {
        setEditingId(null);
    }

    function saveEdit(id: number) {
        router.put(
            customerAddresses.update.url({ customerId, id }),
            { label: editLabel, address: editAddress },
            { onSuccess: () => setEditingId(null) },
        );
    }

    function deleteAddress(id: number) {
        if (!confirm('Bu adresi silmek istediğine emin misin?')) {
            return;
        }

        router.delete(customerAddresses.destroy.url({ customerId, id }));
    }

    function addAddress(e: React.FormEvent) {
        e.preventDefault();

        router.post(
            customerAddresses.store.url(customerId),
            { label: newLabel, address: newAddress },
            {
                onSuccess: () => {
                    setNewLabel('');
                    setNewAddress('');
                },
            },
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <label className="text-sm font-medium">Adresler</label>

            {addresses.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    Henüz kayıtlı adres yok.
                </p>
            )}

            {addresses.map((address) =>
                editingId === address.id ? (
                    <div
                        key={address.id}
                        className="flex flex-col gap-2 rounded-md border p-3"
                    >
                        <input
                            type="text"
                            value={editLabel}
                            onChange={(e) => setEditLabel(e.target.value)}
                            placeholder="Etiket (örn. Ev, İş)"
                            maxLength={100}
                            className="w-full rounded-md border p-2"
                        />
                        <textarea
                            value={editAddress}
                            onChange={(e) => setEditAddress(e.target.value)}
                            maxLength={500}
                            className="w-full rounded-md border p-2"
                        />
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={() => saveEdit(address.id)}
                                className="rounded-md bg-primary px-3 py-1 text-sm text-primary-foreground"
                            >
                                Kaydet
                            </button>
                            <button
                                type="button"
                                onClick={cancelEdit}
                                className="rounded-md border px-3 py-1 text-sm"
                            >
                                Vazgeç
                            </button>
                        </div>
                    </div>
                ) : (
                    <div
                        key={address.id}
                        className="flex items-start justify-between gap-3 rounded-md border p-3"
                    >
                        <div>
                            <p className="text-sm font-medium">
                                {address.label}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {address.address}
                            </p>
                        </div>
                        <div className="flex shrink-0 gap-3">
                            <button
                                type="button"
                                onClick={() => startEdit(address)}
                                className="text-sm text-primary underline"
                            >
                                Düzenle
                            </button>
                            <button
                                type="button"
                                onClick={() => deleteAddress(address.id)}
                                className="text-sm text-destructive underline"
                            >
                                Sil
                            </button>
                        </div>
                    </div>
                ),
            )}

            <form
                onSubmit={addAddress}
                className="flex flex-col gap-2 rounded-md border p-3"
            >
                <p className="text-sm font-medium">Yeni Adres Ekle</p>
                <input
                    type="text"
                    value={newLabel}
                    onChange={(e) => setNewLabel(e.target.value)}
                    placeholder="Etiket (örn. Ev, İş, Okul)"
                    maxLength={100}
                    className="w-full rounded-md border p-2"
                />
                <textarea
                    value={newAddress}
                    onChange={(e) => setNewAddress(e.target.value)}
                    placeholder="Adres"
                    maxLength={500}
                    className="w-full rounded-md border p-2"
                />
                <button
                    type="submit"
                    className="self-start rounded-md border px-3 py-2 text-sm"
                >
                    + Adres Ekle
                </button>
            </form>
        </div>
    );
}
