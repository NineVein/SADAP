import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import useFilters from '../../Hooks/useFilters';
import {
    FilterBar, Pagination, PageHeader, ProgressBar, StatusBadge, ConfirmModal,
    actDelete, actEdit, actView, btnPrimary, inputClass
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
            <Head title="Daftar Panti Asuhan - SADAP" />

            <PageHeader
                title="Daftar Lembaga Panti Asuhan"
                description="Pusat kerja administrasi: pilih panti untuk mengelola anak asuh, data kepengurusan, dokumen legalitas, dan status validasi bantuan."
                actions={can.create && (
                    <Link href="/panti/create" className={btnPrimary}>
                        <Icon name="plus" size={18} /> 
                        <span>Tambah Panti Baru</span>
                    </Link>
                )}
            />

            {/* Tab Penyaringan Status Validasi */}
            <div role="tablist" className="mb-6 flex flex-wrap items-center gap-3">
                {TABS.map((t) => (
                    <button
                        key={t.key}
                        role="tab"
                        aria-selected={f.validasi === t.key}
                        type="button"
                        onClick={() => setF('validasi', t.key)}
                        className={`group flex items-center gap-2.5 rounded-full border px-5 py-2.5 text-sm font-semibold transition-all duration-200 ${
                            f.validasi === t.key
                                ? 'border-[#172b25] bg-[#172b25] text-white shadow-md'
                                : 'border-[#e2e4d8] bg-white text-[#5a6253] hover:border-[#c4c8b9] hover:bg-[#f7f8f3]'
                        }`}
                    >
                        <span>{t.label}</span>
                        <span className={`flex h-6 min-w-[24px] items-center justify-center rounded-full px-2 text-[11px] font-bold transition-colors ${
                            f.validasi === t.key 
                                ? 'bg-white/20 text-white' 
                                : 'bg-[#f7f8f3] text-[#8a9182] group-hover:bg-[#e2e4d8] group-hover:text-[#172b25]'
                        }`}>
                            {counts[t.count]}
                        </span>
                    </button>
                ))}
            </div>

            {/* Filter Bar */}
            <FilterBar
                hasActiveFilters={hasActiveFilters}
                onReset={() => {
                    setF('search', '');
                    setF('status', '');
                    setF('kelurahan_id', '');
                }}
            >
                <div className="sm:col-span-2">
                    <label className="mb-1.5 block text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]" htmlFor="cari">
                        Pencarian Kata Kunci
                    </label>
                    <div className="relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Icon name="search" size={16} className="text-[#8a9182]" />
                        </div>
                        <input
                            id="cari"
                            type="search"
                            value={f.search}
                            onChange={(e) => setF('search', e.target.value)}
                            placeholder="Cari nama panti, alamat, atau no SK..."
                            className={`${inputClass} pl-10`}
                        />
                    </div>
                </div>
                <div>
                    <label className="mb-1.5 block text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]" htmlFor="status">
                        Status Kelembagaan
                    </label>
                    <select id="status" value={f.status} onChange={(e) => setF('status', e.target.value)} className={inputClass}>
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif Beroperasi</option>
                        <option value="nonaktif">Nonaktif / Tutup</option>
                    </select>
                </div>
                {kelurahans.length > 1 && (
                    <div>
                        <label className="mb-1.5 block text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]" htmlFor="kel">
                            Wilayah Kelurahan
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

            {/* Grid Cards Panti */}
            {pantis.data.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#c4c8b9] bg-white py-16 px-4 text-center">
                    <Icon name="inbox" size={48} className="text-[#e2e4d8] mb-4" />
                    <p className="text-sm font-medium text-[#8a9182]">Tidak ada data panti asuhan yang sesuai dengan kriteria pencarian.</p>
                    {hasActiveFilters && (
                        <button 
                            type="button" 
                            onClick={() => { setF('search', ''); setF('status', ''); setF('validasi', ''); setF('kelurahan_id', ''); }}
                            className="mt-3 text-sm font-bold text-[#b08935] hover:underline"
                        >
                            Reset Filter
                        </button>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {pantis.data.map((panti) => (
                        <div key={panti.id} className="group flex flex-col overflow-hidden rounded-xl border border-[#e2e4d8] bg-white shadow-sm transition-all hover:shadow-md hover:border-[#c4c8b9]">
                            {/* Card Header (Logo & Nama) */}
                            <div className="flex items-start gap-4 border-b border-[#f7f8f3] p-5">
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[#e2e4d8] bg-[#f7f8f3]">
                                    {panti.logo ? (
                                        <img src={panti.logo} alt={`Logo ${panti.nama}`} className="h-full w-full object-cover" />
                                    ) : (
                                        <Icon name="home" size={24} className="text-[#c4c8b9]" />
                                    )}
                                </div>
                                <div className="flex-1 overflow-hidden">
                                    <Link href={`/panti/${panti.id}`} className="block truncate text-base font-extrabold text-[#172b25] transition-colors hover:text-[#b08935] hover:underline">
                                        {panti.nama}
                                    </Link>
                                    <p className="mt-1 truncate text-xs font-medium text-[#8a9182]" title={panti.alamat}>
                                        {panti.alamat}
                                    </p>
                                </div>
                            </div>

                            {/* Card Body (Detail & Kelengkapan) */}
                            <div className="flex flex-1 flex-col gap-4 p-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        {panti.kelurahan ? (
                                            <>
                                                <p className="text-[13px] font-bold text-[#172b25]">{panti.kelurahan.nama}</p>
                                                <p className="text-[11px] font-medium text-[#8a9182]">Kec. {panti.kelurahan.kecamatan}</p>
                                            </>
                                        ) : (
                                            <span className="inline-block rounded-md bg-[#fff4f2] px-2 py-1 text-xs font-bold text-[#d25241] border border-[#f9dbd6]">
                                                Belum Ditentukan
                                            </span>
                                        )}
                                    </div>
                                    <span className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-[#e2e4d8] bg-[#f7f8f3] px-3 py-1.5 text-xs font-bold text-[#172b25]">
                                        <Icon name="users" size={14} className="text-[#8a9182]" />
                                        <span>{panti.anaks_count} Anak</span>
                                    </span>
                                </div>

                                <div className="mt-auto">
                                    <div className="mb-2 flex flex-wrap items-center gap-2">
                                        <StatusBadge value={panti.validation_status} />
                                        {panti.status === 'nonaktif' && (
                                            <StatusBadge value="nonaktif" label="Lembaga Nonaktif" />
                                        )}
                                    </div>
                                    
                                    <ProgressBar value={panti.kelengkapan} />
                                    
                                    {panti.kurang.length > 0 && (
                                        <p className="mt-2 text-[11px] font-medium leading-tight text-[#8a9182]">
                                            Kurang: <strong className="text-[#b08935]">{panti.kurang[0]}</strong>
                                            {panti.kurang.length > 1 && ` (+${panti.kurang.length - 1} lainnya)`}
                                        </p>
                                    )}

                                    {panti.validation_status === 'ditolak' && panti.catatan_validasi && (
                                        <div className="mt-3 rounded-lg border border-[#f9dbd6] bg-[#fff4f2] p-2.5">
                                            <p className="text-[11px] font-bold text-[#d25241] leading-tight">
                                                Revisi: <span className="font-medium text-[#c04637]">{panti.catatan_validasi}</span>
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Card Footer (Aksi) */}
                            <div className="flex items-center justify-end gap-2 border-t border-[#f7f8f3] bg-[#fcfdfa] px-5 py-3">
                                <Link href={`/panti/${panti.id}`} className={actView}>
                                    <Icon name={panti.validation_status === 'diajukan' ? 'check' : 'folder-open'} size={15} />
                                    <span className="ml-1 text-xs font-bold">{panti.validation_status === 'diajukan' ? 'Tinjau' : 'Buka'}</span>
                                </Link>
                                {panti.can.update && (
                                    <Link href={`/panti/${panti.id}/edit`} className={actEdit} title="Edit Data">
                                        <Icon name="pencil" size={15} />
                                    </Link>
                                )}
                                {panti.can.delete && (
                                    <button
                                        type="button"
                                        onClick={() => handleDelete(panti)}
                                        className={actDelete}
                                        title="Hapus Data"
                                    >
                                        <Icon name="trash" size={15} />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {pantis.data.length > 0 && (
                <div className="mt-6">
                    <Pagination paginator={pantis} />
                </div>
            )}

            {/* Modal Konfirmasi Hapus Panti */}
            <ConfirmModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, panti: null })}
                onConfirm={confirmDelete}
                title="Hapus Data Panti Asuhan?"
                message={
                    deleteModal.panti
                        ? `Apakah Anda yakin ingin menghapus data panti "${deleteModal.panti.nama}"? Seluruh data anak asuh, dokumen legalitas, dan kepengurusan di dalamnya akan ikut terhapus dan tidak dapat dikembalikan.`
                        : ''
                }
            />
        </AppLayout>
    );
}