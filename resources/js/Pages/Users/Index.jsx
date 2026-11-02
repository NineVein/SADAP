import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminTabs from '../../Components/AdminTabs';
import Icon from '../../Components/Icon';
import useFilters from '../../Hooks/useFilters';
import {
    Card, EmptyRow, FilterBar, PageHeader, Pagination, ConfirmModal,
    actDelete, actEdit, btnPrimary, inputClass, tdClass, thClass,
} from '../../Components/ui';

export default function Index({ users, filters, roles }) {
    const [f, setF] = useFilters('/users', filters);
    const [toggleModal, setToggleModal] = useState({ isOpen: false, user: null });

    function toggle(user) {
        setToggleModal({ isOpen: true, user });
    }

    function confirmToggle() {
        if (!toggleModal.user) return;
        router.patch(`/users/${toggleModal.user.id}/aktif`, {}, {
            preserveScroll: true,
            onSuccess: () => setToggleModal({ isOpen: false, user: null }),
        });
    }

    const hasActiveFilters = Boolean(f.search || f.role_id);

    return (
        <AppLayout>
            <AdminTabs />
            <Head title="Manajemen Pengguna Sistem - DISKATmsa" />

            <PageHeader
                title="Manajemen Pengguna Sistem"
                description="Kelola hak akses untuk Administrator Dinas Sosial, Supervisor/Pimpinan, dan Operator Kelurahan/Desa."
                breadcrumbs={[
                    { label: 'Pengaturan Sistem', href: '/users' },
                    { label: 'Pengguna' },
                ]}
                actions={
                    <Link href="/users/create" className={btnPrimary}>
                        <Icon name="plus" size={17} /> Tambah Pengguna Baru
                    </Link>
                }
            />

            <FilterBar
                hasActiveFilters={hasActiveFilters}
                onReset={() => {
                    setF('search', '');
                    setF('role_id', '');
                }}
            >
                <div className="sm:col-span-2">
                    <label htmlFor="cari" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Pencarian Nama / Email
                    </label>
                    <input
                        id="cari"
                        type="search"
                        value={f.search}
                        onChange={(e) => setF('search', e.target.value)}
                        placeholder="Ketik nama lengkap atau alamat email..."
                        className={inputClass}
                    />
                </div>
                <div className="sm:col-span-2">
                    <label htmlFor="role" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Peran Hak Akses
                    </label>
                    <select id="role" value={f.role_id} onChange={(e) => setF('role_id', e.target.value)} className={inputClass}>
                        <option value="">Semua Peran Hak Akses</option>
                        {roles.map((r) => (
                            <option key={r.id} value={r.id}>{r.label}</option>
                        ))}
                    </select>
                </div>
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Nama & Email</th>
                                <th className={thClass}>Peran Hak Akses</th>
                                <th className={thClass}>Wilayah Penugasan</th>
                                <th className={thClass}>Status Akun</th>
                                <th className={thClass}>Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {users.data.length === 0 ? (
                                <EmptyRow
                                    colSpan={5}
                                    onReset={() => {
                                        setF('search', '');
                                        setF('role_id', '');
                                    }}
                                >
                                    Tidak ada data pengguna yang sesuai dengan filter.
                                </EmptyRow>
                            ) : (
                                users.data.map((u) => (
                                    <tr key={u.id} className="hover:bg-[#fbfcf9] transition">
                                        <td className={tdClass}>
                                            <p className="font-extrabold text-[#1b2e23] text-base">
                                                {u.name}
                                                {u.is_self && (
                                                    <span className="ml-2 inline-block rounded-md bg-[#eef1e6] px-2 py-0.5 text-xs font-bold text-[#203b2e]">
                                                        Akun Anda
                                                    </span>
                                                )}
                                            </p>
                                            <p className="text-xs font-medium text-[#656c5e] mt-0.5">{u.email}</p>
                                        </td>
                                        <td className={tdClass}>
                                            <span className="inline-block rounded-full bg-[#edf0e5] px-3 py-1 text-xs font-bold text-[#203b2e]">
                                                {u.role?.label}
                                            </span>
                                        </td>
                                        <td className={tdClass}>
                                            {u.kelurahan ? (
                                                <span className="font-bold text-[#2a382e]">{u.kelurahan}</span>
                                            ) : (
                                                <span className="text-xs text-[#8a9284]">Tingkat Kabupaten (Semua)</span>
                                            )}
                                        </td>
                                        <td className={tdClass}>
                                            <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold ${
                                                u.is_active
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                                    : 'border-slate-200 bg-slate-100 text-slate-700'
                                            }`}>
                                                <Icon name={u.is_active ? 'check' : 'close'} size={12} />
                                                <span>{u.is_active ? 'Aktif' : 'Dinonaktifkan'}</span>
                                            </span>
                                        </td>
                                        <td className={tdClass}>
                                            <div className="flex gap-2">
                                                <Link href={`/users/${u.id}/edit`} className={actEdit}>
                                                    Edit
                                                </Link>
                                                {!u.is_self && (
                                                    <button
                                                        type="button"
                                                        onClick={() => toggle(u)}
                                                        className={u.is_active ? actDelete : actView}
                                                    >
                                                        {u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={users} />
            </Card>

            {/* Modal Dialog Toggle Status Akun */}
            <ConfirmModal
                isOpen={toggleModal.isOpen}
                onClose={() => setToggleModal({ isOpen: false, user: null })}
                onConfirm={confirmToggle}
                title={
                    toggleModal.user?.is_active
                        ? `Nonaktifkan Akun ${toggleModal.user?.name}?`
                        : `Aktifkan Kembali Akun ${toggleModal.user?.name}?`
                }
                message={
                    toggleModal.user?.is_active
                        ? `Akun ${toggleModal.user?.email} tidak akan dapat login ke sistem sampai diaktifkan kembali oleh administrator.`
                        : `Akun ${toggleModal.user?.email} akan dapat kembali login dan menggunakan hak aksesnya.`
                }
                danger={Boolean(toggleModal.user?.is_active)}
                confirmText={toggleModal.user?.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'}
            />
        </AppLayout>
    );
}
