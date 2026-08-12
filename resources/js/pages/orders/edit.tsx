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

type Product = {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock_quantity: number;
    category: { id: number; vat_rate: string } | null;
};

type OrderItem = {
    id: number;
    product_id: number;
    quantity: number;
};

type Order = {
    id: number;
    customer_id: number;
    customer_address_id: number | null;
    order_items: OrderItem[];
};

type Props = {
    order: Order;
    customers: Customer[];
    products: Product[];
};

type ItemRow = {
    product_id: string;
    quantity: string;
};

export default function OrderEdit({ order, customers, products }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        customer_id: String(order.customer_id),
        customer_address_id: order.customer_address_id
            ? String(order.customer_address_id)
            : '',
        items: order.order_items.map((item): ItemRow => ({
            product_id: String(item.product_id),
            quantity: String(item.quantity),
        })),
    });

    const selectedCustomer = customers.find(
        (c) => String(c.id) === data.customer_id,
    );

    function handleCustomerChange(customerId: string) {
        setData('customer_id', customerId);
        setData('customer_address_id', '');
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

        return Number(product.price) * (Number(item.quantity) || 0);
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
        put(orders.update.url(order.id));
    }

    return (
        <>
            <Head title="Sipariş Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Sipariş Düzenle</h1>

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
                                Teslimat Adresi
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
                                    <option value="">Seçilmedi</option>
                                    {selectedCustomer.addresses.map((addr) => (
                                        <option key={addr.id} value={addr.id}>
                                            {addr.label} — {addr.address}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Bu müşterinin kayıtlı adresi yok.
                                </p>
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
                        Güncelle
                    </button>
                </form>
            </div>
        </>
    );
}
