import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import discountCodes from '@/routes/discount-codes';

type Option = {
    id: number;
    name: string;
};

type DiscountCode = {
    id: number;
    code: string;
    discount_type: string;
    discount_value: string;
    min_order_amount: string | null;
    product_id: number | null;
    category_id: number | null;
};

type Props = {
    discountCode: DiscountCode;
    products: Option[];
    categories: Option[];
};

type TargetType = 'product' | 'category';

export default function DiscountCodeEdit({
    discountCode,
    products,
    categories,
}: Props) {
    const [targetType, setTargetType] = useState<TargetType>(
        discountCode.category_id ? 'category' : 'product',
    );

    const { data, setData, put, processing, errors } = useForm({
        code: discountCode.code,
        discount_type: discountCode.discount_type,
        discount_value: discountCode.discount_value,
        min_order_amount: discountCode.min_order_amount ?? '',
        product_id: discountCode.product_id
            ? String(discountCode.product_id)
            : '',
        category_id: discountCode.category_id
            ? String(discountCode.category_id)
            : '',
    });

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
        put(discountCodes.update.url(discountCode.id));
    }

    return (
        <>
            <Head title="İndirim Kodu Düzenle" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">İndirim Kodu Düzenle</h1>

                <form
                    onSubmit={handleSubmit}
                    className="flex max-w-md flex-col gap-4"
                >
                    <div>
                        <label className="text-sm font-medium">Kod</label>
                        <input
                            type="text"
                            value={data.code}
                            onChange={(e) =>
                                setData('code', e.target.value.toUpperCase())
                            }
                            placeholder="örn. YAZ2026"
                            maxLength={50}
                            className="w-full rounded-md border p-2 uppercase"
                        />
                        {errors.code && (
                            <p className="text-sm text-destructive">
                                {errors.code}
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
                            İndirim Değeri (
                            {data.discount_type === 'percentage' ? '%' : '₺'})
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
                        <label className="text-sm font-medium">
                            Minimum Sipariş Tutarı (₺)
                        </label>
                        <input
                            type="number"
                            min={0}
                            max={99999999.99}
                            step="0.01"
                            value={data.min_order_amount}
                            onChange={(e) =>
                                setData('min_order_amount', e.target.value)
                            }
                            placeholder="Boş bırakılırsa şart aranmaz"
                            className="w-full rounded-md border p-2"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Kodun kullanılabilmesi için sipariş tutarının en az
                            bu kadar olması gerekir.
                        </p>
                        {errors.min_order_amount && (
                            <p className="text-sm text-destructive">
                                {errors.min_order_amount}
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
