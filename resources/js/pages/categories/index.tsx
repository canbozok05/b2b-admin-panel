import { Head, Link } from '@inertiajs/react';

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
                <h1 className="text-2xl font-semibold">Kategoriler</h1>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">İsim</th>
                            <th className="p-2">Üst Kategori</th>
                            <th className="p-2">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.map((category) => (
                            <tr key={category.id} className="border-b">
                                <td className="p-2">{category.name}</td>
                                <td className="p-2">
                                    {category.parent ? category.parent.name : '—'}
                                </td>
                                <td className="p-2">
                                    {category.is_active ? 'Aktif' : 'Pasif'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
