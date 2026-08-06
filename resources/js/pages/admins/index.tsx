import { Head, Link } from '@inertiajs/react';
import admins from '@/routes/admins';

type Role = {
    id: number;
    name: string;
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    roles: Role[];
};

type Props = {
    users: AdminUser[];
};

export default function AdminIndex({ users }: Props) {
    return (
        <>
            <Head title="Sistem Yöneticileri" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Sistem Yöneticileri
                    </h1>
                    <Link
                        href={admins.create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        Yeni Yönetici
                    </Link>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-2">İsim</th>
                            <th className="p-2">Email</th>
                            <th className="p-2">Rol</th>
                            <th className="p-2">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((user) => (
                            <tr key={user.id} className="border-b">
                                <td className="p-2">{user.name}</td>
                                <td className="p-2">{user.email}</td>
                                <td className="p-2">
                                    {user.roles.length > 0
                                        ? user.roles
                                              .map((role) => role.name)
                                              .join(', ')
                                        : '—'}
                                </td>
                                <td className="p-2">
                                    <div className="flex gap-3">
                                        <Link
                                            href={admins.edit.url(user.id)}
                                            className="text-primary underline"
                                        >
                                            Düzenle
                                        </Link>
                                        <Link
                                            href={admins.destroy.url(user.id)}
                                            method="delete"
                                            as="button"
                                            onClick={(e) => {
                                                if (
                                                    !confirm(
                                                        'Bu yöneticiyi silmek istediğine emin misin?',
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
