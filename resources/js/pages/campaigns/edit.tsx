import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import campaigns from '@/routes/campaigns';

type Option = {
    id: number;
    name: string;
};

type Campaign = {
    id: number;
    name: string;
    discount_type: string;
    discount_value: string;
    starts_at: string;
    ends_at: string;
    product_id: number | null;
    category_id: number | null;
};

type Props = {
    campaign: Campaign;
    products: Option[];
    categories: Option[];
};

type TargetType = 'product' | 'category';

// Sunucudan gelen UTC zaman damgasını, datetime-local input'unun beklediği
// "yerel saatte YYYY-MM-DDTHH:mm" biçimine çevirir.
function toDatetimeLocal(value: string): string {
    const date = new Date(value);
    const localTime = new Date(
        date.getTime() - date.getTimezoneOffset() * 60000,
    );

    return localTime.toISOString().slice(0, 16);
}

// datetime-local input'undaki yerel saati, sunucuya göndermeden önce gerçek bir
// UTC zaman damgasına çevirir.
function toUtcIso(localValue: string): string {
    return new Date(localValue).toISOString();
}

export default function CampaignEdit({
    campaign,
    products,
    categories,
}: Props) {
    const [targetType, setTargetType] = useState<TargetType>(
        campaign.category_id ? 'category' : 'product',
    );

    const { data, setData, put, processing, errors, transform } = useForm({
        name: campaign.name,
        discount_type: campaign.discount_type,
        discount_value: campaign.discount_value,
        starts_at: toDatetimeLocal(campaign.starts_at),
        ends_at: toDatetimeLocal(campaign.ends_at),
        product_id: campaign.product_id ? String(campaign.product_id) : '',
        category_id: campaign.category_id ? String(campaign.category_id) : '',
    });

    transform((formData) => ({
        ...formData,
        starts_at: formData.starts_at
            ? toUtcIso(formData.starts_at)
            : formData.starts_at,
        ends_at: formData.ends_at
            ? toUtcIso(formData.ends_at)
            : formData.ends_at,
    }));

    function handleTargetTypeChange(type: TargetType) {
        setTargetType(type);

        if (type === 'product') {
            setData('category_id', '');
        } else {
            setData('product_id', '');
        }
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(campaigns.update.url(campaign.id));
    }

    return (
        <>
            <Head title="Kampanya Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Kampanya Düzenle</h1>

                <form
                    onSubmit={handleSubmit}
                    className="flex max-w-md flex-col gap-4"
                >
                    <div>
                        <label className="text-sm font-medium">
                            Kampanya Adı
                        </label>
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
                        <label className="text-sm font-medium">Kapsam</label>
                        <div className="mt-1 flex gap-4 text-sm">
                            <label className="flex items-center gap-1">
                                <input
                                    type="radio"
                                    checked={targetType === 'product'}
                                    onChange={() =>
                                        handleTargetTypeChange('product')
                                    }
                                />
                                Ürün
                            </label>
                            <label className="flex items-center gap-1">
                                <input
                                    type="radio"
                                    checked={targetType === 'category'}
                                    onChange={() =>
                                        handleTargetTypeChange('category')
                                    }
                                />
                                Kategori
                            </label>
                        </div>
                    </div>

                    {targetType === 'product' ? (
                        <div>
                            <label className="text-sm font-medium">Ürün</label>
                            <select
                                value={data.product_id}
                                onChange={(e) =>
                                    setData('product_id', e.target.value)
                                }
                                className="w-full rounded-md border p-2"
                            >
                                <option value="">Seçiniz</option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>
                                        {product.name}
                                    </option>
                                ))}
                            </select>
                            {errors.product_id && (
                                <p className="text-sm text-destructive">
                                    {errors.product_id}
                                </p>
                            )}
                        </div>
                    ) : (
                        <div>
                            <label className="text-sm font-medium">
                                Kategori
                            </label>
                            <select
                                value={data.category_id}
                                onChange={(e) =>
                                    setData('category_id', e.target.value)
                                }
                                className="w-full rounded-md border p-2"
                            >
                                <option value="">Seçiniz</option>
                                {categories.map((category) => (
                                    <option
                                        key={category.id}
                                        value={category.id}
                                    >
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
                    )}

                    <div>
                        <label className="text-sm font-medium">
                            İndirim Türü
                        </label>
                        <select
                            value={data.discount_type}
                            onChange={(e) =>
                                setData('discount_type', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        >
                            <option value="percentage">Yüzde (%)</option>
                            <option value="fixed">Sabit Tutar (₺)</option>
                        </select>
                    </div>

                    <div>
                        <label className="text-sm font-medium">
                            İndirim Değeri
                        </label>
                        <input
                            type="number"
                            min={0.01}
                            max={
                                data.discount_type === 'percentage'
                                    ? 100
                                    : 99999999.99
                            }
                            step="0.01"
                            value={data.discount_value}
                            onChange={(e) =>
                                setData('discount_value', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        />
                        {errors.discount_value && (
                            <p className="text-sm text-destructive">
                                {errors.discount_value}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Başlangıç</label>
                        <input
                            type="datetime-local"
                            value={data.starts_at}
                            onChange={(e) =>
                                setData('starts_at', e.target.value)
                            }
                            className="w-full rounded-md border p-2"
                        />
                        {errors.starts_at && (
                            <p className="text-sm text-destructive">
                                {errors.starts_at}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">Bitiş</label>
                        <input
                            type="datetime-local"
                            value={data.ends_at}
                            onChange={(e) => setData('ends_at', e.target.value)}
                            className="w-full rounded-md border p-2"
                        />
                        {errors.ends_at && (
                            <p className="text-sm text-destructive">
                                {errors.ends_at}
                            </p>
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
