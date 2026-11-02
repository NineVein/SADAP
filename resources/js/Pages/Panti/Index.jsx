import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import useFilters from '../../Hooks/useFilters';
import {
    Card, EmptyRow, FilterBar, Pagination, PageHeader, ProgressBar, StatusBadge, ConfirmModal,
    actDelete, actEdit, actView, btnPrimary, inputClass, tdClass, thClass,
} from '../../Components/ui';

const TABS = [
    { key: '', label: 'Semua Status', count: 'semua' },
    { key: 'diajukan', label: 'Menunggu Validasi', count: 'diajukan' },
    { key: 'ditolak', label: 'Perlu Revisi', count: 'ditolak' },
    { key: 'draft', label: 'Draft (Belum Diajukan)', count: 'draft' },
    { key: 'tervalidasi', label: 'Tervalidasi Dinsos', count: 'tervalidasi' },
];

export default function Index({ pantis, filters, kelurahans, counts, can }) {
    const [f, setF] = useFilters('/panti', filters);
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, panti: null });

    function handleDelete(panti) {
        setDeleteModal({ isOpen: true, panti });
    }

    function confirmDelete() {
        if (!deleteModal.panti) return;
        router.delete(`/panti/${deleteModal.panti.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModal({ isOpen: false, panti: null }),
        });
    }

    const hasActiveFilters = Boolean(f.search || f.status || f.kelurahan_id);

    return (
        <AppLayout>
            <Head title="Daftar Panti Asuhan - DISKATmsa" />

            <PageHeader
                title="Daftar Lembaga Panti Asuhan"
                description="Pusat kerja administrasi: pilih panti untuk mengelola anak asuh, data kepengurusan, dokumen legalitas, dan status validasi bantuan."
                actions={can.create && (
                    <Link href="/panti/create" className={btnPrimary}>
                        <Icon name="plus" size={17} /> Tambah Panti Baru
                    </Link>
                )}
            />

            {/* Tab Penyaringan Status Validasi */}
            <div role="tablist" className="mb-6 flex flex-wrap gap-2.5">
                {TABS.map((t) => (
                    <button
                        key={t.key}
                        role="tab"
                        aria-selected={f.validasi === t.key}
                        type="button"
                        onClick={() => setF('validasi', t.key)}
                        className={`flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition shadow-2xs ${
                            f.validasi === t.key
                                ? 'border-[#203b2e] bg-[#203b2e] text-[#f7f2e5] shadow-xs'
                                : 'border-[#d6d9cb] bg-white text-[#38433a] hover:bg-[#f1f3e9]'
                        }`}
                    >
                        <span>{t.label}</span>
                        <span className={`rounded-full px-2 py-0.5 text-xs font-extrabold ${
                            f.validasi === t.key ? 'bg-white/20 text-white' : 'bg-[#e9ecdf] text-[#4f5747]'
                        }`}>
                            {counts[t.count]}
                        </span>
                    </button>
                ))}
            </div>

            <FilterBar
                hasActiveFilters={hasActiveFilters}
                onReset={() => {
                    setF('search', '');
                    setF('status', '');
                    setF('kelurahan_id', '');
                }}
            >
                <div className="sm:col-span-2">
                    <label className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]" htmlFor="cari">
                        Pencarian Kata Kunci
                    </label>
                    <input
                        id="cari"
                        type="search"
                        value={f.search}
                        onChange={(e) => setF('search', e.target.value)}
                        placeholder="Ketik nama panti, alamat, atau nomor SK..."
                        className={inputClass}
                    />
                </div>
                <div>
                    <label className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]" htmlFor="status">
                        Status Kelembagaan
                    </label>
                    <select id="status" value={f.status} onChange={(e) => setF('status', e.target.value)} className={inputClass}>
                        <option value="">Semua Status Operasional</option>
                        <option value="aktif">Aktif Beroperasi</option>
                        <option value="nonaktif">Nonaktif / Tutup</option>
                    </select>
                </div>
                {kelurahans.length > 1 && (
                    <div>
                        <label className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]" htmlFor="kel">
                            Wilayah Kelurahan / Desa
                        </label>
                        <select id="kel" value={f.kelurahan_id} onChange={(e) => setF('kelurahan_id', e.target.value)} className={inputClass}>
                            <option value="">Semua Wilayah</option>
                            {kelurahans.map((k) => (
                                <option key={k.id} value={k.id}>
                                    {k.nama} — Kec. {k.kecamatan}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Nama Panti & Alamat</th>
                                <th className={thClass}>Wilayah Kelurahan</th>
                                <th className={thClass}>Anak Asuh</th>
                                <th className={thClass}>Kelengkapan Data</th>
                                <th className={thClass}>Status Validasi</th>
                                <th className={thClass}>Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {pantis.data.length === 0 ? (
                                <EmptyRow
                                    colSpan={6}
                                    onReset={() => {
                                        setF('search', '');
                                        setF('status', '');
                                        setF('validasi', '');
                                        setF('kelurahan_id', '');
                                    }}
                                >
                                    Tidak ada data panti asuhan yang sesuai dengan filter pencarian.
                                </EmptyRow>
                            ) : (
                                pantis.data.map((panti) => (
                                    <tr key={panti.id} className="hover:bg-[#fbfcf9] transition">
                                        <td className={tdClass}>
                                            <Link href={`/panti/${panti.id}`} className="font-extrabold text-base text-[#1b2e23] hover:text-[#203b2e] hover:underline">
                                                {panti.nama}
                                            </Link>
                                            <p className="mt-1 max-w-sm text-xs font-medium text-[#6c7465] leading-relaxed">
                                                {panti.alamat}
                                            </p>
                                        </td>
                                        <td className={tdClass}>
                                            {panti.kelurahan ? (
                                                <div>
                                                    <p className="font-bold text-[#1f2e24]">{panti.kelurahan.nama}</p>
                                                    <p className="text-xs text-[#737b6c]">Kec. {panti.kelurahan.kecamatan}</p>
                                                </div>
                                            ) : (
                                                <span className="inline-block rounded-md bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700">
                                                    Belum Ditentukan
                                                </span>
                                            )}
                                        </td>
                                        <td className={tdClass}>
                                            <span className="inline-flex items-center gap-1.5 rounded-xl border border-[#e2e6d6] bg-white px-3 py-1 font-bold text-[#203b2e]">
                                                <Icon name="users" size={15} />
                                                <span>{panti.anaks_count} Anak</span>
                                            </span>
                                        </td>
                                        <td className={tdClass}>
                                            <ProgressBar value={panti.kelengkapan} />
                                            {panti.kurang.length > 0 && (
                                                <p className="mt-1.5 max-w-[220px] text-xs font-medium text-[#7a8172]">
                                                    Kurang: <strong className="text-amber-800">{panti.kurang[0]}</strong>
                                                    {panti.kurang.length > 1 && ` (+${panti.kurang.length - 1} lainnya)`}
                                                </p>
                                            )}
                                        </td>
                                        <td className={tdClass}>
                                            <div className="flex flex-col items-start gap-1.5">
                                                <StatusBadge value={panti.validation_status} />
                                                {panti.status === 'nonaktif' && (
                                                    <StatusBadge value="nonaktif" label="Lembaga Nonaktif" />
                                                )}
                                            </div>
                                            {panti.validation_status === 'ditolak' && panti.catatan_validasi && (
                                                <p className="mt-1.5 max-w-[220px] rounded-lg border border-rose-200 bg-rose-50 p-2 text-xs font-semibold text-rose-800">
                                                    Revisi: {panti.catatan_validasi}
                                                </p>
                                            )}
                                        </td>
                                        <td className={tdClass}>
                                            <div className="flex flex-wrap gap-2">
                                                <Link href={`/panti/${panti.id}`} className={actView}>
                                                    <Icon name={panti.validation_status === 'diajukan' ? 'check' : 'building'} size={14} />
                                                    {panti.validation_status === 'diajukan' ? 'Tinjau' : 'Buka'}
                                                </Link>
                                                {panti.can.update && (
                                                    <Link href={`/panti/${panti.id}/edit`} className={actEdit}>
                                                        Edit
                                                    </Link>
                                                )}
                                                {panti.can.delete && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(panti)}
                                                        className={actDelete}
                                                    >
                                                        Hapus
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

                <Pagination paginator={pantis} />
            </Card>

            {/* Modal Konfirmasi Hapus Panti */}
            <ConfirmModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, panti: null })}
                onConfirm={confirmDelete}
                title="Hapus Data Panti Asuhan?"
                message={
                    deleteModal.panti
                        ? `Apakah Anda yakin ingin menghapus data panti "${deleteModal.panti.nama}"? Seluruh data anak asuh, dokumen legalitas, dan kepengurusan di dalamnya akan dihapus.`
                        : ''
                }
            />
        </AppLayout>
    );
}
