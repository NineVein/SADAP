import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import KategoriBadge from '../../Components/KategoriBadge';
import KategoriTabs from '../../Components/KategoriTabs';
import useFilters from '../../Hooks/useFilters';
import {
    Card, EmptyRow, FilterBar, PageHeader, Pagination, StatusBadge, ConfirmModal,
    actDelete, actEdit, btnPrimary, inputClass, tdClass, thClass,
} from '../../Components/ui';

export default function Index({ anaks, filters, pantiOptions, options, jenisCounts = {}, can }) {
    const [f, setF] = useFilters('/klien', filters);
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, anak: null });

    function handleDelete(anak) {
        setDeleteModal({ isOpen: true, anak });
    }

    function confirmDelete() {
        if (!deleteModal.anak) return;
        router.delete(`/klien/${deleteModal.anak.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModal({ isOpen: false, anak: null }),
        });
    }

    const hasActiveFilters = Boolean(f.search || f.panti_id || f.kategori || f.status || f.jenis);

    // Pilihan latar belakang menyesuaikan kategori klien yang sedang dipilih.
    const latarOptions = (f.jenis && options.latarBelakang?.[f.jenis]) || options.kategori;

    return (
        <AppLayout>
            <Head title="Data Klien - DISKATmsa" />

            <PageHeader
                title="Klien LKS"
                description="Pencatatan klien di dalam LKS (anak, lanjut usia, dan penyandang disabilitas), dan pemantauan kelayakan bantuan sosial."
                breadcrumbs={[
                    { label: 'Klien' },
                ]}
                actions={can.manage && (
                    <Link
                        href={`/klien/create?${new URLSearchParams({ ...(f.panti_id ? { panti_id: f.panti_id } : {}), ...(f.jenis ? { jenis: f.jenis } : {}) }).toString()}`}
                        className={btnPrimary}
                    >
                        <Icon name="plus" size={17} /> Tambah Klien Baru
                    </Link>
                )}
            />

            {/* Pemisah kategori klien: Anak | Lanjut Usia | Disabilitas */}
            <KategoriTabs
                value={f.jenis}
                onChange={(v) => { setF('jenis', v); setF('panti_id', ''); setF('kategori', ''); }}
                counts={jenisCounts}
                allLabel="Semua Klien"
            />

            <FilterBar
                hasActiveFilters={hasActiveFilters}
                onReset={() => {
                    setF('search', '');
                    setF('panti_id', '');
                    setF('kategori', '');
                    setF('status', '');
                    setF('jenis', '');
                }}
            >
                <div>
                    <label htmlFor="cari" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Pencarian Nama / NIK
                    </label>
                    <input
                        id="cari"
                        type="search"
                        value={f.search}
                        onChange={(e) => setF('search', e.target.value)}
                        placeholder="Ketik nama, atau 16 digit NIK lengkap..."
                        className={inputClass}
                    />
                </div>
                <div>
                    <label htmlFor="panti" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        LKS
                    </label>
                    <select id="panti" value={f.panti_id} onChange={(e) => setF('panti_id', e.target.value)} className={inputClass}>
                        <option value="">Semua LKS</option>
                        {pantiOptions.map((p) => (
                            <option key={p.id} value={p.id}>{p.nama}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="kategori" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Latar Belakang
                    </label>
                    <select id="kategori" value={f.kategori} onChange={(e) => setF('kategori', e.target.value)} className={inputClass}>
                        <option value="">Semua Latar Belakang</option>
                        {Object.entries(latarOptions).map(([v, l]) => (
                            <option key={v} value={v}>{l}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="status" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Status Keaktifan
                    </label>
                    <select id="status" value={f.status} onChange={(e) => setF('status', e.target.value)} className={inputClass}>
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif di LKS</option>
                        <option value="keluar">Sudah Keluar</option>
                    </select>
                </div>
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Nama Klien & NIK</th>
                                <th className={thClass}>LKS</th>
                                <th className={thClass}>L/P & Umur</th>
                                <th className={thClass}>Kategori & Kondisi</th>
                                <th className={thClass}>Status</th>
                                <th className={thClass}>Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {anaks.data.length === 0 ? (
                                <EmptyRow
                                    colSpan={6}
                                    onReset={() => {
                                        setF('search', '');
                                        setF('panti_id', '');
                                        setF('kategori', '');
                                        setF('status', '');
                                        setF('jenis', '');
                                    }}
                                >
                                    Belum ada data klien yang sesuai dengan kriteria penyaringan.
                                </EmptyRow>
                            ) : (
                                anaks.data.map((a) => {
                                    return (
                                        <tr key={a.id} className="hover:bg-[#fbfcf9] transition">
                                            <td className={tdClass}>
                                                <p className="font-extrabold text-[#1b2e23] text-base">{a.nama}</p>
                                                <p className="text-xs font-medium text-[#656d5e] mt-0.5">
                                                    {a.nik_tersamar ? `NIK: ${a.nik_tersamar}` : 'Belum tercatat NIK'}
                                                </p>
                                            </td>
                                            <td className={tdClass}>
                                                {a.panti ? (
                                                    <Link
                                                        href={`/lks/${a.panti.id}`}
                                                        className="font-bold text-[#203b2e] hover:underline"
                                                    >
                                                        {a.panti.nama}
                                                    </Link>
                                                ) : (
                                                    <span className="text-xs text-[#8c9284]">-</span>
                                                )}
                                            </td>
                                            <td className={tdClass}>
                                                <span className="font-medium text-[#2d3a30]">
                                                    {a.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}
                                                </span>
                                                <p className="text-xs text-[#757e70]">{a.umur ? `${a.umur} Tahun` : '-'}</p>
                                            </td>
                                            <td className={tdClass}>
                                                <KategoriBadge value={a.kategori_klien} />
                                                <p className="mt-1 text-xs font-semibold text-[#3e473b]">
                                                    {options.kategori[a.kategori] ?? a.kategori}
                                                </p>
                                                {a.jenis_disabilitas && (
                                                    <p className="text-xs font-semibold text-violet-800">
                                                        {options.jenisDisabilitas?.[a.jenis_disabilitas] ?? a.jenis_disabilitas}
                                                    </p>
                                                )}
                                                {a.tingkat_ketergantungan && (
                                                    <p className="text-xs text-[#757e70]">
                                                        {options.tingkat?.[a.tingkat_ketergantungan] ?? a.tingkat_ketergantungan}
                                                    </p>
                                                )}
                                            </td>
                                            <td className={tdClass}><StatusBadge value={a.status} /></td>
                                            <td className={tdClass}>
                                                <div className="flex flex-wrap gap-2">
                                                    {can.manage && (
                                                        <Link href={`/klien/${a.id}/edit`} className={actEdit}>
                                                            Edit
                                                        </Link>
                                                    )}
                                                    {can.manage && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDelete(a)}
                                                            className={actDelete}
                                                        >
                                                            Hapus
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={anaks} />
            </Card>

            {/* Modal Konfirmasi Hapus Data Klien */}
            <ConfirmModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, anak: null })}
                onConfirm={confirmDelete}
                title="Hapus Data Klien?"
                message={
                    deleteModal.anak
                        ? `Apakah Anda yakin ingin menghapus data klien "${deleteModal.anak.nama}"? Data klien akan dihapus.`
                        : ''
                }
            />
        </AppLayout>
    );
}
