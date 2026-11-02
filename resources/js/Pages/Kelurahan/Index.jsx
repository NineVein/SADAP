import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminTabs from '../../Components/AdminTabs';
import Icon from '../../Components/Icon';
import useFilters from '../../Hooks/useFilters';
import {
    Card, EmptyRow, Field, FilterBar, PageHeader, Pagination, ConfirmModal,
    actDelete, actEdit, btnPrimary, btnSecondary, inputClass, tdClass, thClass,
} from '../../Components/ui';

function WilayahFields({ form }) {
    const { data, setData, errors } = form;
    return (
        <>
            <Field label="Nama Kelurahan / Desa" required error={errors.nama} hint="Contoh: Wewelen atau Rinegetan">
                <input
                    value={data.nama}
                    onChange={(e) => setData('nama', e.target.value)}
                    placeholder="Nama desa / kelurahan..."
                    className={inputClass}
                />
            </Field>
            <Field label="Kecamatan" required error={errors.kecamatan} hint="Contoh: Tondano Barat">
                <input
                    value={data.kecamatan}
                    onChange={(e) => setData('kecamatan', e.target.value)}
                    placeholder="Nama kecamatan..."
                    className={inputClass}
                />
            </Field>
            <Field label="Jenis Wilayah" required error={errors.tipe}>
                <select value={data.tipe} onChange={(e) => setData('tipe', e.target.value)} className={inputClass}>
                    <option value="kelurahan">Kelurahan</option>
                    <option value="desa">Desa</option>
                </select>
            </Field>
        </>
    );
}

export default function Index({ kelurahans, filters }) {
    const [f, setF] = useFilters('/kelurahan', filters);
    const [editingId, setEditingId] = useState(null);
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, item: null });

    const create = useForm({ nama: '', kecamatan: '', tipe: 'kelurahan' });
    const edit = useForm({ nama: '', kecamatan: '', tipe: 'kelurahan' });

    function submitCreate(e) {
        e.preventDefault();
        create.post('/kelurahan', { preserveScroll: true, onSuccess: () => create.reset('nama') });
    }

    function startEdit(k) {
        edit.clearErrors();
        edit.setData({ nama: k.nama, kecamatan: k.kecamatan, tipe: k.tipe });
        setEditingId(k.id);
    }

    function submitEdit(e) {
        e.preventDefault();
        edit.put(`/kelurahan/${editingId}`, { preserveScroll: true, onSuccess: () => setEditingId(null) });
    }

    function handleDelete(k) {
        setDeleteModal({ isOpen: true, item: k });
    }

    function confirmDelete() {
        if (!deleteModal.item) return;
        router.delete(`/kelurahan/${deleteModal.item.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModal({ isOpen: false, item: null }),
        });
    }

    const hasActiveFilters = Boolean(f.search);

    return (
        <AppLayout>
            <AdminTabs />
            <Head title="Kelurahan & Desa - DISKATmsa" />

            <PageHeader
                title="Master Data Kelurahan & Desa"
                description="Daftar wilayah administratif di Kabupaten Minahasa yang dipakai untuk memetakan panti dan membatasi akses operator."
                breadcrumbs={[
                    { label: 'Pengaturan Sistem', href: '/users' },
                    { label: 'Kelurahan & Desa' },
                ]}
            />

            <Card
                title="Tambah Wilayah Baru"
                description="Daftarkan kelurahan atau desa baru di Kabupaten Minahasa"
                className="mb-7"
            >
                <form onSubmit={submitCreate} className="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <WilayahFields form={create} />
                    <div className="sm:col-span-2 lg:col-span-1 lg:pt-[28px]">
                        <button type="submit" disabled={create.processing} className={`${btnPrimary} w-full`}>
                            <Icon name="plus" size={16} /> Tambah Wilayah
                        </button>
                    </div>
                </form>
            </Card>

            <FilterBar hasActiveFilters={hasActiveFilters} onReset={() => setF('search', '')}>
                <div className="sm:col-span-4">
                    <label htmlFor="cari" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Pencarian Wilayah
                    </label>
                    <input
                        id="cari"
                        type="search"
                        value={f.search}
                        onChange={(e) => setF('search', e.target.value)}
                        placeholder="Ketik nama kelurahan, desa, atau kecamatan..."
                        className={inputClass}
                    />
                </div>
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Nama Wilayah</th>
                                <th className={thClass}>Kecamatan</th>
                                <th className={thClass}>Jenis</th>
                                <th className={thClass}>Panti Terdaftar</th>
                                <th className={thClass}>Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {kelurahans.data.length === 0 ? (
                                <EmptyRow colSpan={5} onReset={() => setF('search', '')}>
                                    Tidak ada data kelurahan atau desa yang cocok.
                                </EmptyRow>
                            ) : (
                                kelurahans.data.map((k) =>
                                    editingId === k.id ? (
                                        <tr key={k.id} className="bg-[#f7f9f2]">
                                            <td colSpan={5} className="p-5">
                                                <form onSubmit={submitEdit} className="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                                    <WilayahFields form={edit} />
                                                    <div className="flex gap-2 sm:col-span-2 lg:col-span-1 lg:pt-[28px]">
                                                        <button type="submit" disabled={edit.processing} className={btnPrimary}>
                                                            Simpan
                                                        </button>
                                                        <button type="button" onClick={() => setEditingId(null)} className={btnSecondary}>
                                                            Batal
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    ) : (
                                        <tr key={k.id} className="hover:bg-[#fbfcf9] transition">
                                            <td className={`${tdClass} font-bold text-[#1b2e23] text-base`}>{k.nama}</td>
                                            <td className={`${tdClass} font-semibold text-[#5a6253]`}>{k.kecamatan}</td>
                                            <td className={tdClass}>
                                                <span className="inline-block rounded-md bg-[#edf0e5] px-2.5 py-0.5 text-xs font-bold capitalize text-[#203b2e]">
                                                    {k.tipe}
                                                </span>
                                            </td>
                                            <td className={tdClass}>
                                                <span className="inline-flex items-center gap-1 rounded-lg border border-[#e2e6d6] bg-white px-2.5 py-1 text-xs font-bold text-[#203b2e]">
                                                    <Icon name="building" size={13} />
                                                    <span>{k.pantis_count} Panti</span>
                                                </span>
                                            </td>
                                            <td className={tdClass}>
                                                <div className="flex gap-2">
                                                    <button type="button" onClick={() => startEdit(k)} className={actEdit}>
                                                        Edit
                                                    </button>
                                                    {k.pantis_count === 0 && (
                                                        <button type="button" onClick={() => handleDelete(k)} className={actDelete}>
                                                            Hapus
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )
                                )
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={kelurahans} />
            </Card>

            {/* Modal Konfirmasi Hapus Wilayah */}
            <ConfirmModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, item: null })}
                onConfirm={confirmDelete}
                title={`Hapus Wilayah ${deleteModal.item?.nama}?`}
                message={`Apakah Anda yakin ingin menghapus wilayah ${deleteModal.item?.nama} (${deleteModal.item?.kecamatan})? Tindakan ini tidak dapat dibatalkan.`}
            />
        </AppLayout>
    );
}
