import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import Badge from '../../Components/Badge';
import {
    Card, PageHeader, StatusBadge, ConfirmModal,
    actView, btnDanger, btnPrimary, btnSecondary,
    formatDate, formatDateTime, inputClass,
} from '../../Components/ui';
import pantiService from '../../Services/pantiService';

// Tab-tab modular mengadopsi struktur dinas/src/features/panti/tabs/
import ProfilTab from './Tabs/ProfilTab';
import AnakTab from './Tabs/AnakTab';
import PengurusTab from './Tabs/PengurusTab';
import DokumenTab from './Tabs/DokumenTab';
import JenisBantuanTab from './Tabs/JenisBantuanTab';
import AktivitasTab from './Tabs/AktivitasTab';

/* ---------------- Validasi & Pemeriksaan Kelayakan ---------------- */
function ValidationPanel({ panti, can = {}, onGo, onConfirmAjukan }) {
    const [rejecting, setRejecting] = useState(false);
    const reject = useForm({ catatan: '' });

    function submitReject(e) {
        e.preventDefault();
        reject.post(`/panti/${panti.id}/tolak`, {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
        });
    }

    const profil = can.update ? { label: 'Lengkapi Profil', href: `/panti/${panti.id}/edit` } : null;
    const aksi = {
        profil,
        wilayah: profil,
        no_sk: profil,
        tanggal_berdiri: profil,
        kontak: profil,
        pengelola: profil,
        pengurus: can.manage ? { label: 'Atur Pengurus', tab: 'pengurus' } : null,
        anak: can.manage ? { label: 'Tambah Anak', href: `/anak/create?panti_id=${panti.id}&dari=panti` } : null,
        dokumen: can.manage ? { label: 'Unggah Berkas', tab: 'dokumen' } : null,
        jenis_bantuan: can.manage ? { label: 'Pilih Bantuan', tab: 'jenis' } : null,
    };

    return (
        <Card title="Pemeriksaan & Validasi Kelayakan" description="Status legalitas dan kelayakan data untuk bantuan Dinas Sosial">
            <div className="flex flex-wrap items-center gap-2.5 mb-4">
                <StatusBadge value={panti.validation_status} />
                {panti.validation_status === 'diajukan' && (
                    <span className="text-xs font-semibold text-[#6a7263]">
                        Diajukan pada {formatDateTime(panti.diajukan_at)}
                    </span>
                )}
                {panti.validation_status === 'tervalidasi' && (
                    <span className="text-xs font-semibold text-[#6a7263]">
                        Diverifikasi oleh: <strong className="text-[#203b2e]">{panti.validator ?? '-'}</strong> ({formatDateTime(panti.validated_at)})
                    </span>
                )}
            </div>

            {panti.validation_status === 'ditolak' && panti.catatan_validasi && (
                <div className="mb-4 rounded-2xl border border-rose-300 bg-rose-50/90 p-4 text-sm text-rose-950 shadow-2xs">
                    <div className="flex items-center gap-2 font-bold text-rose-900 mb-1">
                        <Icon name="alert" size={16} />
                        <span>Catatan Perbaikan dari Dinas Sosial:</span>
                    </div>
                    <p className="mt-1 leading-relaxed text-rose-800">{panti.catatan_validasi}</p>
                </div>
            )}

            {/* Checklist Persyaratan */}
            <div className="space-y-2.5 mt-4">
                <p className="text-xs font-bold uppercase tracking-wider text-[#6c7465]">Daftar Poin Kelengkapan:</p>
                <ul className="space-y-2">
                    {panti.checklist.map((item) => {
                        const act = !item.done ? aksi[item.key] : null;
                        return (
                            <li
                                key={item.key}
                                className={`flex items-center gap-3 rounded-xl border p-2.5 text-sm transition ${
                                    item.done
                                        ? 'border-emerald-200 bg-emerald-50/50 text-[#1e2f24]'
                                        : 'border-[#e4e7d9] bg-white text-[#5f6758]'
                                }`}
                            >
                                <span
                                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${
                                        item.done ? 'bg-emerald-600 text-white' : 'bg-[#e7e9dc] text-[#868d7e]'
                                    }`}
                                >
                                    <Icon name={item.done ? 'check' : 'x'} size={14} />
                                </span>
                                <span className={`flex-1 font-medium ${item.done ? 'font-semibold text-[#1e2f24]' : ''}`}>
                                    {item.label}
                                </span>
                                {act && (act.href ? (
                                    <Link href={act.href} className={actView}>{act.label}</Link>
                                ) : (
                                    <button type="button" onClick={() => onGo(act.tab)} className={actView}>{act.label}</button>
                                ))}
                            </li>
                        );
                    })}
                </ul>
            </div>

            {/* Tombol Tindakan Validasi */}
            <div className="mt-6 pt-5 border-t border-[#edf0e5] space-y-3">
                {can.submit && panti.validation_status !== 'diajukan' && (
                    <div>
                        <button
                            type="button"
                            onClick={onConfirmAjukan}
                            disabled={!panti.kelengkapan.lengkap}
                            className={`w-full ${btnPrimary}`}
                        >
                            <Icon name="check" size={16} />
                            <span>Ajukan ke Dinas Sosial</span>
                        </button>
                        {!panti.kelengkapan.lengkap && (
                            <p className="mt-2 text-center text-xs text-amber-800">
                                Lengkapi seluruh poin checklist di atas sebelum mengajukan validasi.
                            </p>
                        )}
                    </div>
                )}

                {can.review && panti.validation_status === 'diajukan' && (
                    <div className="flex flex-col sm:flex-row gap-2.5">
                        <button
                            type="button"
                            onClick={() => pantiService.setujuiValidasi(panti.id)}
                            className={`flex-1 ${btnPrimary}`}
                        >
                            <Icon name="check" size={16} />
                            <span>Setujui Validasi</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setRejecting((v) => !v)}
                            className={btnDanger}
                        >
                            <Icon name="close" size={16} />
                            <span>Minta Revisi</span>
                        </button>
                    </div>
                )}

                {can.review && rejecting && (
                    <form onSubmit={submitReject} className="mt-3 space-y-3 rounded-2xl border border-rose-200 bg-rose-50/70 p-4">
                        <p className="text-xs font-bold text-rose-900">Alasan Penolakan / Catatan Perbaikan:</p>
                        <textarea
                            rows={3}
                            value={reject.data.catatan}
                            onChange={(e) => reject.setData('catatan', e.target.value)}
                            placeholder="Tuliskan secara spesifik hal yang perlu diperbaiki operator..."
                            className={inputClass}
                            autoFocus
                        />
                        {reject.errors.catatan && (
                            <p className="text-xs font-semibold text-rose-700">{reject.errors.catatan}</p>
                        )}
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
            </div>
        </Card>
    );
}

/* ---------------- Halaman Utama Profil Panti ---------------- */
export default function Show({
    panti,
    tab: initialTab,
    anaks = [],
    pengurus = [],
    dokumens = [],
    jenisBantuan = [],
    activityLogs = [],
    dokumenWajibStatus = {},
    options = {},
    can = {},
}) {
    const [tab, setTab] = useState(initialTab || 'profil');
    const [pengurusKey, setPengurusKey] = useState(0);

    const [confirmModal, setConfirmModal] = useState({
        isOpen: false,
        type: '',
        title: '',
        message: '',
        onConfirm: null,
        danger: true,
    });

    const tabs = [
        { key: 'profil', label: 'Profil', icon: 'building', count: null },
        { key: 'anak', label: 'Anak Binaan', icon: 'users', count: anaks.length },
        { key: 'pengurus', label: 'Pengurus', icon: 'shield', count: pengurus.length },
        { key: 'dokumen', label: 'Dokumen Instansi', icon: 'file', count: dokumens.length },
        { key: 'jenis', label: 'Jenis Bantuan', icon: 'report', count: jenisBantuan.filter((j) => j.dibutuhkan).length },
        { key: 'aktivitas', label: 'Aktivitas', icon: 'clock', count: activityLogs.length },
    ];

    function goTab(key) {
        setTab(key);
        if (key === 'pengurus') setPengurusKey((k) => k + 1);
    }

    function handleDeletePanti() {
        setConfirmModal({
            isOpen: true,
            title: 'Hapus Data Panti Asuhan?',
            message: `Apakah Anda yakin ingin menghapus data panti "${panti.nama}"? Seluruh data anak, pengurus, dan dokumen di dalamnya juga akan terhapus.`,
            danger: true,
            onConfirm: () => pantiService.hapus(panti.id),
        });
    }

    function handleRequestDelete(type, id, nama) {
        let title = '';
        let url = '';

        if (type === 'anak') {
            title = `Hapus data anak asuh "${nama}"?`;
            url = `/anak/${id}`;
        } else if (type === 'pengurus') {
            title = `Hapus data pengurus "${nama}"?`;
            url = `/pengurus/${id}`;
        } else if (type === 'dokumen') {
            title = `Hapus berkas dokumen "${nama}"?`;
            url = `/dokumen/${id}`;
        }

        setConfirmModal({
            isOpen: true,
            title,
            message: 'Tindakan ini tidak dapat dibatalkan. Berkas atau data terkait akan dihapus dari sistem.',
            danger: true,
            onConfirm: () =>
                router.delete(url, {
                    preserveScroll: true,
                    onSuccess: () => setConfirmModal((prev) => ({ ...prev, isOpen: false })),
                }),
        });
    }

    function handleConfirmAjukan() {
        setConfirmModal({
            isOpen: true,
            title: 'Ajukan Validasi Data Panti?',
            message: `Apakah Anda yakin data panti "${panti.nama}" sudah lengkap dan siap diverifikasi oleh petugas Dinas Sosial?`,
            danger: false,
            onConfirm: () => {
                pantiService.ajukanValidasi(panti.id, {
                    onSuccess: () => setConfirmModal((prev) => ({ ...prev, isOpen: false })),
                });
            },
        });
    }

  return (
        <AppLayout>
            <Head title={`${panti.nama} - SADAP`} />

            <PageHeader
                title={panti.nama}
                description={
                    panti.kelurahan
                        ? `${panti.kelurahan.nama}, Kecamatan ${panti.kelurahan.kecamatan}`
                        : 'Wilayah kelurahan/desa belum ditentukan'
                }
                breadcrumbs={[
                    { label: 'Panti Asuhan', href: '/panti' },
                    { label: panti.nama },
                ]}
                actions={
                    <>
                        <Link href="/panti" className={btnSecondary}>
                            <Icon name="arrow-left" size={16} /> Kembali ke Daftar
                        </Link>
                        {can.update && (
                            <Link href={`/panti/${panti.id}/edit`} className={btnPrimary}>
                                <Icon name="pencil" size={16} /> Edit Data Panti
                            </Link>
                        )}
                        {can.delete && (
                            <button type="button" onClick={handleDeletePanti} className={btnDanger}>
                                <Icon name="trash" size={16} /> Hapus Panti
                            </button>
                        )}
                    </>
                }
            />
            
            {/* ... (lanjutan kode grid items dan tabs di bawahnya tetap sama) ... */}

            <div className="grid items-start gap-7 xl:grid-cols-[1.55fr_1fr]">
                <div className="space-y-7">
                    {/* Kartu Navigasi Tab Kerja */}
                    <Card padded={false}>
                        <div className="border-b border-[#e5e7dc] bg-[#fbfcf9] px-6 pt-3">
                            <div role="tablist" className="flex flex-wrap gap-2">
                                {tabs.map((t) => (
                                    <button
                                        key={t.key}
                                        role="tab"
                                        aria-selected={tab === t.key}
                                        type="button"
                                        onClick={() => setTab(t.key)}
                                        className={`flex items-center gap-2 rounded-t-xl px-4 py-3 text-sm font-bold transition border-b-2 ${
                                            tab === t.key
                                                ? 'border-[#203b2e] bg-white text-[#203b2e] shadow-2xs'
                                                : 'border-transparent text-[#6e7667] hover:text-[#203b2e] hover:bg-[#f1f3e9]'
                                        }`}
                                    >
                                        <Icon name={t.icon} size={17} />
                                        <span>{t.label}</span>
                                        {t.count !== null && (
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-bold ${
                                                    tab === t.key ? 'bg-[#203b2e] text-white' : 'bg-[#e7e9de] text-[#555d4e]'
                                                }`}
                                            >
                                                {t.count}
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="overflow-x-auto p-6">
                            {tab === 'profil' && (
                                <ProfilTab
                                    panti={panti}
                                    can={can}
                                />
                            )}
                            {tab === 'anak' && (
                                <AnakTab
                                    panti={panti}
                                    anaks={anaks}
                                    options={options}
                                    can={can}
                                    onRequestDelete={handleRequestDelete}
                                />
                            )}
                            {tab === 'pengurus' && (
                                <PengurusTab
                                    panti={panti}
                                    pengurus={pengurus}
                                    can={can}
                                    openKey={pengurusKey}
                                    onRequestDelete={handleRequestDelete}
                                />
                            )}
                            {tab === 'dokumen' && (
                                <DokumenTab
                                    panti={panti}
                                    dokumens={dokumens}
                                    dokumenWajibStatus={dokumenWajibStatus}
                                    options={options}
                                    can={can}
                                    onRequestDelete={handleRequestDelete}
                                />
                            )}
                            {tab === 'jenis' && (
                                <JenisBantuanTab
                                    panti={panti}
                                    jenisBantuan={jenisBantuan}
                                    can={can}
                                />
                            )}
                            {tab === 'aktivitas' && (
                                <AktivitasTab
                                    logs={activityLogs}
                                />
                            )}
                        </div>
                    </Card>
                </div>

                {/* Panel Validasi */}
                <ValidationPanel
                    panti={panti}
                    can={can}
                    onGo={goTab}
                    onConfirmAjukan={handleConfirmAjukan}
                />
            </div>

            {/* Modal Dialog Konfirmasi */}
            <ConfirmModal
                isOpen={confirmModal.isOpen}
                onClose={() => setConfirmModal((prev) => ({ ...prev, isOpen: false }))}
                onConfirm={confirmModal.onConfirm}
                title={confirmModal.title}
                message={confirmModal.message}
                danger={confirmModal.danger !== false}
            />
        </AppLayout>
    );
}
