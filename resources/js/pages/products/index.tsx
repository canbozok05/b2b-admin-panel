import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import products from '@/routes/products';

type Product = {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock_quantity: number;
    is_published: boolean;
    category: { id: number; name: string } | null;
};

type Props = {
    products: Product[];
    filters: {
        search: string | null;
    };
};

export default function ProductIndex({ products: allProducts, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function handleSearchChange(value: string) {
        setSearch(value);
        router.get(
            products.index.url(),
            { search: value },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Ürünler" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Ürünler</h1>

                <input
                    type="text"
                    value={search}
                    onChange={(e) => handleSearchChange(e.target.value)}
                    placeholder="Ürün ismine göre ara..."
                    className="w-full max-w-sm rounded-md border p-2"
                />

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">İsim</th>
                            <th className="p-2">SKU</th>
                            <th className="p-2">Kategori</th>
                            <th className="p-2">Fiyat</th>
                            <th className="p-2">Stok</th>
                            <th className="p-2">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allProducts.map((product) => (
                            <tr key={product.id} className="border-b">
                                <td className="p-2">{product.name}</td>
                                <td className="p-2">{product.sku}</td>
                                <td className="p-2">
                                    {product.category ? product.category.name : '—'}
                                </td>
                                <td className="p-2">{product.price}</td>
                                <td className="p-2">{product.stock_quantity}</td>
                                <td className="p-2">
                                    {product.is_published ? 'Yayında' : 'Taslak'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
