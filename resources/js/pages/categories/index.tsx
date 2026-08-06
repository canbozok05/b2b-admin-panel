import { Head, Link } from '@inertiajs/react';
import categoryRoutes from '@/routes/categories';

type Category = {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    parent: { id: number; name: string } | null;
};

type Props = {
    categories: Category[];
};

export default function CategoryIndex({ categories }: Props) {
    return (
        <>
            <Head title="Kategoriler" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Kategoriler</h1>
                    <Link
                        href={categoryRoutes.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni Kategori
                    </Link>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">İsim</th>
                            <th className="p-2">Üst Kategori</th>
                            <th className="p-2">Durum</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.map((category) => (
                            <tr key={category.id} className="border-b">
                                <td className="p-2">{category.name}</td>
                                <td className="p-2">
                                    {category.parent
                                        ? category.parent.name
                                        : '—'}
                                </td>
                                <td className="p-2">
                                    {category.is_active ? 'Aktif' : 'Pasif'}
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={categoryRoutes.edit.url(
                                                category.id,
                                            )}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        <Link
                                            href={categoryRoutes.destroy.url(
                                                category.id,
                                            )}
                                            method="delete"
                                            as="button"
                                            onClick={(e) => {
                                                if (
                                                    !confirm(
                                                        'Bu kategoriyi silmek istediğine emin misin?',
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
