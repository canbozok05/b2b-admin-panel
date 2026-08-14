import { Head, Link } from '@inertiajs/react';
import discountCodes from '@/routes/discount-codes';

type DiscountCode = {
    id: number;
    code: string;
    discount_type: string;
    discount_value: string;
    min_order_amount: string | null;
    product: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
};

type Props = {
    discountCodes: DiscountCode[];
};

function formatDiscount(discountCode: DiscountCode): string {
    return discountCode.discount_type === 'percentage'
        ? `%${discountCode.discount_value}`
        : `${discountCode.discount_value} ₺`;
}

export default function DiscountCodeIndex({
    discountCodes: allDiscountCodes,
}: Props) {
    return (
        <>
            <Head title="İndirim Kodları" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">İndirim Kodları</h1>
                    <Link
                        href={discountCodes.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni İndirim Kodu
                    </Link>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">Kod</th>
                            <th className="p-2">Kapsam</th>
                            <th className="p-2">İndirim</th>
                            <th className="p-2">Min. Sipariş Tutarı</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allDiscountCodes.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-4 text-center text-muted-foreground"
                                >
                                    Henüz indirim kodu oluşturulmadı.
                                </td>
                            </tr>
                        )}
                        {allDiscountCodes.map((discountCode) => (
                            <tr key={discountCode.id} className="border-b">
                                <td className="p-2 font-mono font-medium">
                                    {discountCode.code}
                                </td>
                                <td className="p-2">
                                    {discountCode.product
                                        ? `Ürün: ${discountCode.product.name}`
                                        : `Kategori: ${discountCode.category?.name}`}
                                </td>
                                <td className="p-2">
                                    {formatDiscount(discountCode)}
                                </td>
                                <td className="p-2">
                                    {discountCode.min_order_amount
                                        ? `${discountCode.min_order_amount} ₺`
                                        : '—'}
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={discountCodes.edit.url(
                                                discountCode.id,
                                            )}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        <Link
                                            href={discountCodes.destroy.url(
                                                discountCode.id,
                                            )}
                                            method="delete"
                                            as="button"
                                            onClick={(e) => {
                                                if (
                                                    !confirm(
                                                        'Bu indirim kodunu silmek istediğine emin misin?',
                                                    )
                                                ) {
                                                    e.preventDefault();
                                                }
                                            }}
                                            className="text-destructive underline"
                                        >
                                            Sil
                                        </Link>
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
