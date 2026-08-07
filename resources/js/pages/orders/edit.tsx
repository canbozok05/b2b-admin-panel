import { Head, useForm } from '@inertiajs/react';
import orders from '@/routes/orders';

type Customer = {
    id: number;
    name: string;
};

type Product = {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock_quantity: number;
};

type OrderItem = {
    id: number;
    product_id: number;
    quantity: number;
};

type Order = {
    id: number;
    customer_id: number;
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
        items: order.order_items.map((item): ItemRow => ({
            product_id: String(item.product_id),
            quantity: String(item.quantity),
        })),
    });

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

    function productPrice(productId: string): number {
        const product = products.find((p) => String(p.id) === productId);

        return product ? Number(product.price) : 0;
    }

    const total = data.items.reduce(
        (sum, item) =>
            sum + productPrice(item.product_id) * (Number(item.quantity) || 0),
        0,
    );

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
                                setData('customer_id', e.target.value)
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

                    <div className="flex flex-col gap-2">
                        <label className="text-sm font-medium">Ürünler</label>

                        {data.items.map((item, index) => {
                            const selectedProduct = products.find(
                                (p) => String(p.id) === item.product_id,
                            );

                            return (
                                <div
                                    key={index}
                                    className="flex items-start gap-2"
                                >
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
                                        <option value="">Ürün seçiniz</option>
                                        {products.map((product) => (
                                            <option
                                                key={product.id}
                                                value={product.id}
                                            >
                                                {product.name} ({product.sku}) —
                                                stok: {product.stock_quantity}
                                            </option>
                                        ))}
                                    </select>

                                    <input
                                        type="number"
                                        min={1}
                                        max={selectedProduct?.stock_quantity}
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

                    <p className="text-right text-lg font-semibold">
                        Toplam:{' '}
                        {total.toLocaleString('tr-TR', {
                            minimumFractionDigits: 2,
                        })}{' '}
                        ₺
                    </p>

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
