import { Head, useForm } from '@inertiajs/react';
import orders from '@/routes/orders';

type Address = {
    id: number;
    label: string;
    address: string;
};

type Customer = {
    id: number;
    name: string;
    addresses: Address[];
};

type ActiveCampaign = {
    id: number;
    name: string;
    discount_type: string;
    discount_value: string;
    ends_at: string;
};

type Product = {
    id: number;
    name: string;
    sku: string;
    price: string;
    discounted_price: string;
    active_campaign: ActiveCampaign | null;
    stock_quantity: number;
    category: { id: number; vat_rate: string } | null;
};

type Props = {
    customers: Customer[];
    products: Product[];
};

type ItemRow = {
    product_id: string;
    quantity: string;
};

export default function OrderCreate({ customers, products }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        customer_address_id: '',
        new_address_label: '',
        new_address_text: '',
        items: [{ product_id: '', quantity: '1' }] as ItemRow[],
    });

    const selectedCustomer = customers.find(
        (c) => String(c.id) === data.customer_id,
    );

    function handleCustomerChange(customerId: string) {
        setData('customer_id', customerId);
        setData('customer_address_id', '');
        setData('new_address_label', '');
        setData('new_address_text', '');
    }

    function updateItem(index: number, field: keyof ItemRow, value: string) {
        const next = data.items.map((item, i) =>
            i === index ? { ...item, [field]: value } : item,
        );
        setData('items', next);
    }

    function addItem() {
        setData('items', [...data.items, { product_id: '', quantity: '1' }]);
    }

    function removeItem(index: number) {
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    }

    function findProduct(productId: string): Product | undefined {
        return products.find((p) => String(p.id) === productId);
    }

    function lineGross(item: ItemRow): number {
        const product = findProduct(item.product_id);

        if (!product) {
            return 0;
        }

        return Number(product.discounted_price) * (Number(item.quantity) || 0);
    }

    // Fiyat KDV dahil olduğu için, KDV oranı ve tutarını geriye doğru hesaplıyoruz:
    // net = kdv dahil fiyat / (1 + oran/100), kdv tutarı = kdv dahil fiyat - net.
    function lineVat(item: ItemRow): { rate: number; amount: number } {
        const product = findProduct(item.product_id);

        if (!product || !product.category) {
            return { rate: 0, amount: 0 };
        }

        const rate = Number(product.category.vat_rate);
        const gross = lineGross(item);
        const net = gross / (1 + rate / 100);

        return { rate, amount: gross - net };
    }

    const total = data.items.reduce((sum, item) => sum + lineGross(item), 0);
    const totalVat = data.items.reduce(
        (sum, item) => sum + lineVat(item).amount,
        0,
    );

    function formatTl(value: number): string {
        return value.toLocaleString('tr-TR', { minimumFractionDigits: 2 });
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(orders.store.url());
    }

    return (
        <>
            <Head title="Yeni Sipariş" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Yeni Sipariş</h1>

                <form
                    onSubmit={handleSubmit}
                    className="flex max-w-2xl flex-col gap-4"
                >
                    <div>
                        <label className="text-sm font-medium">Müşteri</label>
                        <select
                            value={data.customer_id}
                            onChange={(e) =>
                                handleCustomerChange(e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        >
                            <option value="">Seçiniz</option>
                            {customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.name}
                                </option>
                            ))}
                        </select>
                        {errors.customer_id && (
                            <p className="text-sm text-destructive">
                                {errors.customer_id}
                            </p>
                        )}
                    </div>

                    {selectedCustomer && (
                        <div>
                            <label className="text-sm font-medium">
                                Teslimat Adresi (zorunlu)
                            </label>
                            {selectedCustomer.addresses.length > 0 ? (
                                <select
                                    value={data.customer_address_id}
                                    onChange={(e) =>
                                        setData(
                                            'customer_address_id',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border p-2"
                                >
                                    <option value="">Seçiniz</option>
                                    {selectedCustomer.addresses.map((addr) => (
                                        <option key={addr.id} value={addr.id}>
                                            {addr.label} — {addr.address}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <div className="flex flex-col gap-2 rounded-md border p-3">
                                    <p className="text-sm text-muted-foreground">
                                        Bu müşterinin kayıtlı adresi yok, bu
                                        siparişle birlikte ekle.
                                    </p>

                                    <div>
                                        <label className="text-sm font-medium">
                                            Adres Etiketi
                                        </label>
                                        <input
                                            type="text"
                                            value={data.new_address_label}
                                            onChange={(e) =>
                                                setData(
                                                    'new_address_label',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="örn. Ev, İş, Okul"
                                            maxLength={100}
                                            className="w-full rounded-md border p-2"
                                        />
                                        {errors.new_address_label && (
                                            <p className="text-sm text-destructive">
                                                {errors.new_address_label}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium">
                                            Adres
                                        </label>
                                        <textarea
                                            value={data.new_address_text}
                                            onChange={(e) =>
                                                setData(
                                                    'new_address_text',
                                                    e.target.value,
                                                )
                                            }
                                            maxLength={500}
                                            className="w-full rounded-md border p-2"
                                        />
                                        {errors.new_address_text && (
                                            <p className="text-sm text-destructive">
                                                {errors.new_address_text}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                            {errors.customer_address_id && (
                                <p className="text-sm text-destructive">
                                    {errors.customer_address_id}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="flex flex-col gap-2">
                        <label className="text-sm font-medium">Ürünler</label>

                        {data.items.map((item, index) => {
                            const selectedProduct = findProduct(
                                item.product_id,
                            );
                            const vat = lineVat(item);

                            return (
                                <div
                                    key={index}
                                    className="flex flex-col gap-1 rounded-md border p-2"
                                >
                                    <div className="flex items-start gap-2">
                                        <select
                                            value={item.product_id}
                                            onChange={(e) =>
                                                updateItem(
                                                    index,
                                                    'product_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="flex-1 rounded-md border p-2"
                                        >
                                            <option value="">
                                                Ürün seçiniz
                                            </option>
                                            {products.map((product) => (
                                                <option
                                                    key={product.id}
                                                    value={product.id}
                                                >
                                                    {product.name} (
                                                    {product.sku}) — stok:{' '}
                                                    {product.stock_quantity}
                                                    {product.active_campaign
                                                        ? ' — Kampanyalı'
                                                        : ''}
                                                </option>
                                            ))}
                                        </select>

                                        <input
                                            type="number"
                                            min={1}
                                            max={
                                                selectedProduct?.stock_quantity
                                            }
                                            value={item.quantity}
                                            onChange={(e) =>
                                                updateItem(
                                                    index,
                                                    'quantity',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-24 rounded-md border p-2"
                                        />

                                        <button
                                            type="button"
                                            onClick={() => removeItem(index)}
                                            disabled={data.items.length === 1}
                                            className="rounded-md border px-3 py-2 text-sm text-destructive disabled:opacity-40"
                                        >
                                            Kaldır
                                        </button>
                                    </div>

                                    {selectedProduct && (
                                        <p className="text-xs text-muted-foreground">
                                            KDV: %{vat.rate} (
                                            {formatTl(vat.amount)} ₺)
                                        </p>
                                    )}

                                    {selectedProduct?.active_campaign && (
                                        <p className="text-xs text-green-600">
                                            {
                                                selectedProduct.active_campaign
                                                    .name
                                            }{' '}
                                            ile birim fiyat{' '}
                                            {formatTl(
                                                Number(selectedProduct.price),
                                            )}{' '}
                                            ₺ yerine{' '}
                                            {formatTl(
                                                Number(
                                                    selectedProduct.discounted_price,
                                                ),
                                            )}{' '}
                                            ₺
                                        </p>
                                    )}
                                </div>
                            );
                        })}

                        <button
                            type="button"
                            onClick={addItem}
                            className="self-start rounded-md border px-3 py-2 text-sm"
                        >
                            + Ürün Ekle
                        </button>

                        {errors.items && (
                            <p className="text-sm text-destructive">
                                {errors.items}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col items-end gap-1 text-sm">
                        <p className="text-muted-foreground">
                            Toplam KDV: {formatTl(totalVat)} ₺
                        </p>
                        <p className="text-lg font-semibold">
                            Toplam: {formatTl(total)} ₺
                        </p>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-primary p-2 text-primary-foreground"
                    >
                        Siparişi Oluştur
                    </button>
                </form>
            </div>
        </>
    );
}
