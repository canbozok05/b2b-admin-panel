import { Head } from '@inertiajs/react';

import { dashboard } from '@/routes';

const statistics = [
    {
        title: 'Toplam Müşteri',
        value: '0',
        description: 'Sisteme kayıtlı müşteriler',
    },
    {
        title: 'Aylık Satış Hacmi',
        value: '₺0,00',
        description: 'Bu ay gerçekleşen toplam satış',
    },
    {
        title: 'Bekleyen Siparişler',
        value: '0',
        description: 'İşlem bekleyen siparişler',
    },
    {
        title: 'Kritik Stok',
        value: '0',
        description: 'Stok miktarı 10’dan az olan ürünler',
    },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Ana Panel" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        B2B Yönetim Paneli
                    </h1>

                    <p className="text-sm text-muted-foreground">
                        Stok, müşteri ve sipariş işlemlerini yönetin.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {statistics.map((statistic) => (
                        <div
                            key={statistic.title}
                            className="rounded-xl border bg-card p-5 shadow-sm"
                        >
                            <p className="text-sm text-muted-foreground">
                                {statistic.title}
                            </p>

                            <p className="mt-2 text-3xl font-bold">
                                {statistic.value}
                            </p>

                            <p className="mt-1 text-xs text-muted-foreground">
                                {statistic.description}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="rounded-xl border bg-card p-5">
                    <h2 className="text-lg font-semibold">
                        Kritik Stok Uyarıları
                    </h2>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Şu anda kritik stok seviyesinde ürün bulunmuyor.
                    </p>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Ana Panel',
            href: dashboard(),
        },
    ],
};