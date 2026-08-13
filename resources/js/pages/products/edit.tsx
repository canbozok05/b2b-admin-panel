import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import RichTextEditor from '@/components/rich-text-editor';
import products from '@/routes/products';

type ProductImage = {
    id: number;
    path: string;
};

type Category = {
    id: number;
    name: string;
};

type Product = {
    id: number;
    category_id: number;
    name: string;
    sku: string;
    description: string | null;
    price: string;
    stock_quantity: number;
    is_published: boolean;
    images: ProductImage[];
};

type Props = {
    product: Product;
    categories: Category[];
};

export default function ProductEdit({ product, categories }: Props) {
    const [previews, setPreviews] = useState<string[]>([]);
    const [deleteImageIds, setDeleteImageIds] = useState<number[]>([]);

    const { data, setData, put, processing, errors } = useForm({
        category_id: String(product.category_id),
        name: product.name,
        sku: product.sku,
        description: product.description ?? '',
        price: product.price,
        stock_quantity: String(product.stock_quantity),
        is_published: product.is_published,
        images: [] as File[],
        delete_image_ids: [] as number[],
    });

    function handleImagesChange(e: React.ChangeEvent<HTMLInputElement>) {
        const files = Array.from(e.target.files ?? []);
        setData('images', files);
        setPreviews(files.map((file) => URL.createObjectURL(file)));
    }

    function toggleDeleteImage(id: number) {
        const next = deleteImageIds.includes(id)
            ? deleteImageIds.filter((imageId) => imageId !== id)
            : [...deleteImageIds, id];

        setDeleteImageIds(next);
        setData('delete_image_ids', next);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(products.update.url(product.id));
    }

    return (
        <>
            <Head title="Ürün Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Ürün Düzenle</h1>

                <form
                    onSubmit={handleSubmit}
                    className="flex max-w-xl flex-col gap-4"
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
                        <label className="text-sm font-medium">SKU</label>
                        <input
                            type="text"
                            value={data.sku}
                            onChange={(e) => setData('sku', e.target.value)}
                            maxLength={100}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.sku && (
                            <p className="text-sm text-destructive">
                                {errors.sku}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Kategori</label>
                        <select
                            value={data.category_id}
                            onChange={(e) =>
                                setData('category_id', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        >
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                        {errors.category_id && (
                            <p className="text-sm text-destructive">
                                {errors.category_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Açıklama</label>
                        <RichTextEditor
                            value={data.description}
                            onChange={(value) => setData('description', value)}
                        />
                    </div>

                    <div>
                        <label className="text-sm font-medium">Fiyat (₺)</label>
                        <input
                            type="number"
                            step="0.01"
                            min={0}
                            max={99999999.99}
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.price && (
                            <p className="text-sm text-destructive">
                                {errors.price}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">
                            Stok Miktarı
                        </label>
                        <input
                            type="number"
                            min={0}
                            max={1000000}
                            value={data.stock_quantity}
                            onChange={(e) =>
                                setData('stock_quantity', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        />
                        {errors.stock_quantity && (
                            <p className="text-sm text-destructive">
                                {errors.stock_quantity}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={data.is_published}
                            onChange={(e) =>
                                setData('is_published', e.target.checked)
                            }
                            id="is_published"
                        />
                        <label
                            htmlFor="is_published"
                            className="text-sm font-medium"
                        >
                            Yayında
                        </label>
                    </div>

                    {product.images.length > 0 && (
                        <div>
                            <label className="text-sm font-medium">
                                Mevcut Görseller
                            </label>
                            <p className="text-xs text-muted-foreground">
                                Silmek istediğin görsele tıkla, işaretlensin.
                            </p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {product.images.map((image) => {
                                    const marked = deleteImageIds.includes(
                                        image.id,
                                    );

                                    return (
                                        <button
                                            type="button"
                                            key={image.id}
                                            onClick={() =>
                                                toggleDeleteImage(image.id)
                                            }
                                            className={`relative h-20 w-20 overflow-hidden rounded-md border-2 ${marked ? 'border-destructive opacity-40' : 'border-transparent'}`}
                                        >
                                            <img
                                                src={`/storage/${image.path}`}
                                                className="h-full w-full object-cover"
                                            />
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    <div>
                        <label className="text-sm font-medium">
                            Yeni Görsel Ekle
                        </label>
                        <input
                            type="file"
                            multiple
                            accept="image/*"
                            onChange={handleImagesChange}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.images && (
                            <p className="text-sm text-destructive">
                                {errors.images}
                            </p>
                        )}

                        {previews.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-2">
                                {previews.map((src, index) => (
                                    <img
                                        key={index}
                                        src={src}
                                        className="h-20 w-20 rounded-md border object-cover"
                                    />
                                ))}
                            </div>
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
