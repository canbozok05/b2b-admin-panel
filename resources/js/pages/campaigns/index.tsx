import { Head, Link } from '@inertiajs/react';
import campaigns from '@/routes/campaigns';

type Campaign = {
    id: number;
    name: string;
    discount_type: string;
    discount_value: string;
    starts_at: string;
    ends_at: string;
    status: string;
    product: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
};

type Props = {
    campaigns: Campaign[];
};

function formatDiscount(campaign: Campaign): string {
    return campaign.discount_type === 'percentage'
        ? `%${campaign.discount_value}`
        : `${campaign.discount_value} ₺`;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleString('tr-TR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function statusClassName(status: string): string {
    if (status === 'Aktif') {
        return 'text-green-600';
    }

    if (status === 'Planlanan') {
        return 'text-muted-foreground';
    }

    return 'text-destructive';
}

export default function CampaignIndex({ campaigns: allCampaigns }: Props) {
    return (
        <>
            <Head title="Kampanyalar" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Kampanyalar</h1>
                    <Link
                        href={campaigns.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni Kampanya
                    </Link>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">Kampanya</th>
                            <th className="p-2">Kapsam</th>
                            <th className="p-2">İndirim</th>
                            <th className="p-2">Başlangıç</th>
                            <th className="p-2">Bitiş</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {allCampaigns.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="p-4 text-center text-muted-foreground"
                                >
                                    Henüz kampanya oluşturulmadı.
                                </td>
                            </tr>
                        )}
                        {allCampaigns.map((campaign) => (
                            <tr key={campaign.id} className="border-b">
                                <td className="p-2">{campaign.name}</td>
                                <td className="p-2">
                                    {campaign.product
                                        ? `Ürün: ${campaign.product.name}`
                                        : `Kategori: ${campaign.category?.name}`}
                                </td>
                                <td className="p-2">
                                    {formatDiscount(campaign)}
                                </td>
                                <td className="p-2">
                                    {formatDate(campaign.starts_at)}
                                </td>
                                <td className="p-2">
                                    {formatDate(campaign.ends_at)}
                                </td>
                                <td className="p-2">
                                    <span
                                        className={`font-medium ${statusClassName(campaign.status)}`}
                                    >
                                        {campaign.status}
                                    </span>
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={campaigns.edit.url(
                                                campaign.id,
                                            )}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        <Link
                                            href={campaigns.destroy.url(
                                                campaign.id,
                                            )}
                                            method="delete"
                                            as="button"
                                            onClick={(e) => {
                                                if (
                                                    !confirm(
                                                        'Bu kampanyayı silmek istediğine emin misin?',
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
