import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import RichTextEditor from '@/components/rich-text-editor';
import products from '@/routes/products';

type Category = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
};

export default function ProductCreate({ categories }: Props) {
    const [previews, setPreviews] = useState<string[]>([]);

    const { data, setData, post, processing, errors } = useForm({
        category_id: '',
        name: '',
        sku: '',
        description: '',
        price: '',
        stock_quantity: '',
        is_published: false,
        images: [] as File[],
    });

    function handleImagesChange(e: React.ChangeEvent<HTMLInputElement>) {
        const files = Array.from(e.target.files ?? []);
        setData('images', files);
        setPreviews(files.map((file) => URL.createObjectURL(file)));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(products.store.url());
    }

    return (
        <>
            <Head title="Yeni Ürün" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Yeni Ürün</h1>

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
                            <option value="">Seçiniz</option>
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

                    <div>
                        <label className="text-sm font-medium">Görseller</label>
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
                        Kaydet
                    </button>
                </form>
            </div>
        </>
    );
}
