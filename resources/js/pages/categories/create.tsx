import { Head, useForm } from '@inertiajs/react';
import categories from '@/routes/categories';

type Category = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
};

export default function CategoryCreate({ categories: allCategories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        parent_id: '',
        is_active: true,
        vat_rate: '20',
        critical_stock_threshold: '10',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(categories.store.url());
    }

    return (
        <>
            <Head title="Yeni Kategori" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Yeni Kategori</h1>

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
                            {allCategories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
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
                        Kaydet
                    </button>
                </form>
            </div>
        </>
    );
}
