import { Head, useForm } from '@inertiajs/react';
import categoryRoutes from '@/routes/categories';

type Category = {
    id: number;
    name: string;
    parent_id: number | null;
    is_active: boolean;
    vat_rate: string;
    critical_stock_threshold: number;
};

type Props = {
    category: Category;
    categories: Category[];
};

export default function CategoryEdit({ category, categories }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: category.name,
        parent_id: category.parent_id ? String(category.parent_id) : '',
        is_active: category.is_active,
        vat_rate: category.vat_rate,
        critical_stock_threshold: String(category.critical_stock_threshold),
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(categoryRoutes.update.url(category.id));
    }

    return (
        <>
            <Head title="Kategori Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Kategori Düzenle</h1>

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
                        <label className="text-sm font-medium">
                            Üst Kategori
                        </label>
                        <select
                            value={data.parent_id}
                            onChange={(e) =>
                                setData('parent_id', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        >
                            <option value="">Yok</option>
                            {categories
                                .filter((c) => c.id !== category.id)
                                .map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                        </select>
                    </div>

                    <div>
                        <label className="text-sm font-medium">
                            KDV Oranı (%)
                        </label>
                        <input
                            type="number"
                            min={0}
                            max={100}
                            step="0.01"
                            value={data.vat_rate}
                            onChange={(e) =>
                                setData('vat_rate', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        />
                        {errors.vat_rate && (
                            <p className="text-sm text-destructive">
                                {errors.vat_rate}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">
                            Kritik Stok Eşiği
                        </label>
                        <input
                            type="number"
                            min={0}
                            max={100000}
                            step="1"
                            value={data.critical_stock_threshold}
                            onChange={(e) =>
                                setData(
                                    'critical_stock_threshold',
                                    e.target.value,
                                )
                            }
                            className="w-full rounded-md border p-2"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Bu kategorideki bir ürünün stoğu bu sayının altına
                            düşünce panelde kritik olarak işaretlenir.
                        </p>
                        {errors.critical_stock_threshold && (
                            <p className="text-sm text-destructive">
                                {errors.critical_stock_threshold}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            id="is_active"
                        />
                        <label
                            htmlFor="is_active"
                            className="text-sm font-medium"
                        >
                            Aktif
                        </label>
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
