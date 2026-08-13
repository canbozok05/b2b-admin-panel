import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import products from '@/routes/products';
import type { Auth } from '@/types/auth';

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
    is_published: boolean;
    category: { id: number; name: string } | null;
    images: { id: number; path: string }[];
};

type Props = {
    products: Product[];
    filters: {
        search: string | null;
    };
};

export default function ProductIndex({
    products: allProducts,
    filters,
}: Props) {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const isSuperAdmin = (auth.roles ?? []).includes('Süper Admin');

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
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Ürünler</h1>
                    <Link
                        href={products.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni Ürün
                    </Link>
                </div>

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
                            <th className="p-2">Görsel</th>
                            <th className="p-2">İsim</th>
                            <th className="p-2">SKU</th>
                            <th className="p-2">Kategori</th>
                            <th className="p-2">Fiyat</th>
                            <th className="p-2">Stok</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allProducts.map((product) => (
                            <tr key={product.id} className="border-b">
                                <td className="p-2">
                                    {product.images.length > 0 ? (
                                        <img
                                            src={`/storage/${product.images[0].path}`}
                                            className="h-12 w-12 rounded-md border object-cover"
                                        />
                                    ) : (
                                        <div className="h-12 w-12 rounded-md border bg-muted" />
                                    )}
                                </td>
                                <td className="p-2">{product.name}</td>
                                <td className="p-2">{product.sku}</td>
                                <td className="p-2">
                                    {product.category
                                        ? product.category.name
                                        : '—'}
                                </td>
                                <td className="p-2">
                                    {product.active_campaign ? (
                                        <div>
                                            <span className="text-muted-foreground line-through">
                                                {product.price}
                                            </span>{' '}
                                            <span className="font-semibold text-green-600">
                                                {product.discounted_price} ₺
                                            </span>
                                            <p className="text-xs text-muted-foreground">
                                                {product.active_campaign.name}
                                            </p>
                                        </div>
                                    ) : (
                                        product.price
                                    )}
                                </td>
                                <td className="p-2">
                                    {product.stock_quantity}
                                </td>
                                <td className="p-2">
                                    {product.is_published
                                        ? 'Yayında'
                                        : 'Taslak'}
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={products.edit.url(product.id)}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        {isSuperAdmin && (
                                            <Link
                                                href={products.destroy.url(
                                                    product.id,
                                                )}
                                                method="delete"
                                                as="button"
                                                onClick={(e) => {
                                                    if (
                                                        !confirm(
                                                            'Bu ürünü silmek istediğine emin misin?',
                                                        )
                                                    ) {
                                                        e.preventDefault();
                                                    }
                                                }}
                                                className="text-destructive underline"
                                            >
                                                Sil
                                            </Link>
                                        )}
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
