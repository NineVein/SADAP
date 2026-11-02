import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import DesilForm from '../../Components/DesilForm';
import useFilters from '../../Hooks/useFilters';
import {
    Card, EmptyRow, FilterBar, PageHeader, Pagination, StatusBadge, ConfirmModal,
    actDelete, actEdit, actView, btnDanger, btnPrimary, btnSecondary, formatDate, inputClass, tdClass, thClass,
} from '../../Components/ui';

function PendingRow({ item }) {
    const [rejecting, setRejecting] = useState(false);
    const reject = useForm({ catatan: '' });

    function submitReject(e) {
        e.preventDefault();
        reject.post(`/desil/${item.id}/tolak`, {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
        });
    }

    return (
        <li className="px-6 py-4.5 transition hover:bg-[#fbfcf9]">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="min-w-0 flex-1">
                    <p className="text-base font-bold text-[#1e2f24]">
                        {item.anak?.nama} <span className="font-semibold text-xs text-[#6e7667]">· Asal Panti: {item.anak?.panti?.nama}</span>
                    </p>
                    <p className="mt-1 text-xs sm:text-sm text-[#555d4e]">
                        Usulan Desil: <strong className="text-[#203b2e] font-extrabold text-sm">Desil {item.desil}</strong>
                        {item.anak?.desil ? ` (Sebelumnya Desil ${item.anak.desil})` : ' (Belum ada desil sebelumnya)'} · Sumber: <span className="font-bold text-[#203b2e]">{item.sumber.toUpperCase()}</span>
                        {item.tanggal_data ? ` · Tanggal Data: ${formatDate(item.tanggal_data)}` : ''} · Dilaporkan oleh: <span className="font-semibold text-[#1e2f24]">{item.pelapor?.name ?? '-'}</span>
                    </p>
                    {item.catatan && (
                        <p className="mt-1.5 rounded-lg border border-[#e2e6d6] bg-[#f9faf5] p-2 text-xs text-[#525a4a]">
                            Catatan Pelapor: {item.catatan}
                        </p>
                    )}
                </div>
                {!rejecting && (
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => router.post(`/desil/${item.id}/setujui`, {}, { preserveScroll: true })}
                            className={btnPrimary}
                        >
                            <Icon name="check" size={16} /> Setujui Desil
                        </button>
                        <button
                            type="button"
                            onClick={() => setRejecting(true)}
                            className={btnDanger}
                        >
                            <Icon name="close" size={16} /> Tolak
                        </button>
                    </div>
                )}
            </div>
            {rejecting && (
                <form onSubmit={submitReject} className="mt-4 max-w-xl space-y-3 rounded-2xl border border-rose-200 bg-rose-50/70 p-4">
                    <p className="text-xs font-bold text-rose-900">Alasan Penolakan Usulan Desil:</p>
                    <textarea
                        rows={2}
                        value={reject.data.catatan}
                        onChange={(e) => reject.setData('catatan', e.target.value)}
                        placeholder="Tuliskan alasan penolakan atau instruksi pembetulan data..."
                        className={inputClass}
                        autoFocus
                    />
                    {reject.errors.catatan && <p className="text-xs font-bold text-rose-700">{reject.errors.catatan}</p>}
                    <div className="flex gap-2">
                        <button type="submit" disabled={reject.processing} className={btnDanger}>
                            Kirim Penolakan
                        </button>
                        <button type="button" onClick={() => setRejecting(false)} className={btnSecondary}>
                            Batal
                        </button>
                    </div>
                </form>
            )}
        </li>
    );
}

export default function Index({ anaks, filters, pantiOptions, options, desilMenunggu = [], counts, can }) {
    const [f, setF] = useFilters('/anak', filters);
    const [selected, setSelected] = useState(null);
    const [deleteModal, setDeleteModal] = useState({ isOpen: false, anak: null });

    function handleDelete(anak) {
        setDeleteModal({ isOpen: true, anak });
    }

    function confirmDelete() {
        if (!deleteModal.anak) return;
        router.delete(`/anak/${deleteModal.anak.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModal({ isOpen: false, anak: null }),
        });
    }

    const desilChips = [
        { key: '', label: 'Semua Anak' },
        { key: 'belum', label: `Belum Ada Desil (${counts.belum_desil})` },
        { key: 'ada', label: 'Sudah Ada Desil' },
        { key: 'menunggu', label: 'Menunggu Persetujuan' },
    ];

    const hasActiveFilters = Boolean(f.search || f.panti_id || f.kategori || f.status);

    return (
        <AppLayout>
            <Head title="Data Anak & Desil Kesejahteraan - DISKATmsa" />

            <PageHeader
                title="Data Anak Asuh & Desil Kesejahteraan"
                description="Pencatatan data anak binaan, verifikasi profil kemiskinan (desil 1-10), dan pemantauan kelayakan bantuan sosial."
                breadcrumbs={[
                    { label: 'Anak & Desil' },
                ]}
                actions={can.manage && (
                    <Link href={`/anak/create${f.panti_id ? `?panti_id=${f.panti_id}` : ''}`} className={btnPrimary}>
                        <Icon name="plus" size={17} /> Tambah Anak Baru
                    </Link>
                )}
            />

            {/* Antrean Persetujuan Desil Reviewer */}
            {can.review && desilMenunggu.length > 0 && (
                <Card
                    title={`Laporan Desil yang Menunggu Persetujuan Anda (${desilMenunggu.length})`}
                    description="Usulan angka desil dari operator yang memerlukan persetujuan Dinas Sosial"
                    padded={false}
                    className="mb-7 border-amber-300 shadow-amber-50 shadow-sm"
                >
                    <ul className="divide-y divide-[#edf0e5]">
                        {desilMenunggu.map((item) => (
                            <PendingRow key={item.id} item={item} />
                        ))}
                    </ul>
                </Card>
            )}

            {/* Formulir Pelaporan / Pengubahan Desil */}
            {selected && (
                <div className="mb-7">
                    <DesilForm
                        key={selected.id}
                        anak={selected}
                        pantiName={selected.panti?.nama}
                        options={options}
                        isReviewer={can.review}
                        onDone={() => setSelected(null)}
                    />
                </div>
            )}

            {/* Chip Tab Filter Desil */}
            <div role="tablist" className="mb-5 flex flex-wrap gap-2.5">
                {desilChips.map((c) => (
                    <button
                        key={c.key}
                        role="tab"
                        aria-selected={f.desil === c.key}
                        type="button"
                        onClick={() => setF('desil', c.key)}
                        className={`flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition shadow-2xs ${
                            f.desil === c.key
                                ? 'border-[#203b2e] bg-[#203b2e] text-[#f7f2e5] shadow-xs'
                                : 'border-[#d6d9cb] bg-white text-[#38433a] hover:bg-[#f1f3e9]'
                        }`}
                    >
                        {c.label}
                    </button>
                ))}
            </div>

            <FilterBar
                hasActiveFilters={hasActiveFilters}
                onReset={() => {
                    setF('search', '');
                    setF('panti_id', '');
                    setF('kategori', '');
                    setF('status', '');
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
                        placeholder="Ketik nama atau 16 digit NIK..."
                        className={inputClass}
                    />
                </div>
                <div>
                    <label htmlFor="panti" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Panti Asuhan
                    </label>
                    <select id="panti" value={f.panti_id} onChange={(e) => setF('panti_id', e.target.value)} className={inputClass}>
                        <option value="">Semua Panti Asuhan</option>
                        {pantiOptions.map((p) => (
                            <option key={p.id} value={p.id}>{p.nama}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="kategori" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Kategori Asuhan
                    </label>
                    <select id="kategori" value={f.kategori} onChange={(e) => setF('kategori', e.target.value)} className={inputClass}>
                        <option value="">Semua Kategori</option>
                        {Object.entries(options.kategori).map(([v, l]) => (
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
                        <option value="aktif">Aktif di Panti</option>
                        <option value="keluar">Sudah Keluar</option>
                    </select>
                </div>
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Nama Anak & NIK</th>
                                <th className={thClass}>Panti Asuhan</th>
                                <th className={thClass}>L/P & Umur</th>
                                <th className={thClass}>Kategori</th>
                                <th className={thClass}>Desil Kesejahteraan</th>
                                <th className={thClass}>Status</th>
                                <th className={thClass}>Aksi Kerja</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {anaks.data.length === 0 ? (
                                <EmptyRow
                                    colSpan={7}
                                    onReset={() => {
                                        setF('search', '');
                                        setF('panti_id', '');
                                        setF('kategori', '');
                                        setF('status', '');
                                        setF('desil', '');
                                    }}
                                >
                                    Belum ada data anak yang sesuai dengan kriteria penyaringan.
                                </EmptyRow>
                            ) : (
                                anaks.data.map((a) => {
                                    const menunggu = a.laporan?.status === 'menunggu';
                                    const ditolak = a.laporan?.status === 'ditolak';
                                    return (
                                        <tr key={a.id} className="hover:bg-[#fbfcf9] transition">
                                            <td className={tdClass}>
                                                <p className="font-extrabold text-[#1b2e23] text-base">{a.nama}</p>
                                                <p className="text-xs font-medium text-[#656d5e] mt-0.5">
                                                    {a.nik ? `NIK: ${a.nik}` : 'Belum tercatat NIK'}
                                                </p>
                                            </td>
                                            <td className={tdClass}>
                                                {a.panti ? (
                                                    <Link
                                                        href={`/panti/${a.panti.id}`}
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
                                                <span className="inline-block rounded-lg bg-[#eff2e7] px-2.5 py-1 text-xs font-bold text-[#203b2e]">
                                                    {options.kategori[a.kategori] ?? a.kategori}
                                                </span>
                                            </td>
                                            <td className={tdClass}>
                                                {a.desil ? (
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">
                                                        Desil {a.desil}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs font-bold text-amber-700">Belum ada</span>
                                                )}
                                                {menunggu && (
                                                    <p className="mt-1">
                                                        <StatusBadge value="diajukan" label={`Usulan Desil ${a.laporan.desil}`} />
                                                    </p>
                                                )}
                                                {ditolak && (
                                                    <p className="mt-1 max-w-[200px] text-xs font-semibold text-rose-700">
                                                        Ditolak: {a.laporan.catatan_review}
                                                    </p>
                                                )}
                                            </td>
                                            <td className={tdClass}><StatusBadge value={a.status} /></td>
                                            <td className={tdClass}>
                                                <div className="flex flex-wrap gap-2">
                                                    {can.report && !menunggu && (
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setSelected(a);
                                                                window.scrollTo({ top: 0, behavior: 'smooth' });
                                                            }}
                                                            className={actView}
                                                        >
                                                            {a.desil ? 'Ubah Desil' : 'Laporkan Desil'}
                                                        </button>
                                                    )}
                                                    {can.manage && (
                                                        <Link href={`/anak/${a.id}/edit`} className={actEdit}>
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

            {/* Modal Konfirmasi Hapus Data Anak */}
            <ConfirmModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, anak: null })}
                onConfirm={confirmDelete}
                title="Hapus Data Anak Asuh?"
                message={
                    deleteModal.anak
                        ? `Apakah Anda yakin ingin menghapus data anak asuh "${deleteModal.anak.nama}"? Data anak dan riwayat desilnya akan dihapus.`
                        : ''
                }
            />
        </AppLayout>
    );
}
