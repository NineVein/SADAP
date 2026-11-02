import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import Icon from '../../../Components/Icon';
import { formatDate, formatBytes } from '../../../Utils/format';
import {
    Field, EmptyRow, actDelete, actView, btnPrimary,
    inputClass, tdClass, thClass,
} from '../../../Components/ui';

/**
 * Tab Dokumen Legalitas Instansi mengadopsi struktur bersih dari dinas DokumenTab.
 */
export default function DokumenTab({ panti, dokumens = [], dokumenWajibStatus = {}, options = {}, can = {}, onRequestDelete }) {
    const [fileKey, setFileKey] = useState(0);
    const form = useForm({
        panti_id: panti.id,
        jenis: '',
        judul: '',
        nomor: '',
        berlaku_sampai: '',
        file: null,
    });

    function upload(e) {
        e.preventDefault();
        form.post('/dokumen', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('jenis', 'judul', 'nomor', 'berlaku_sampai', 'file');
                setFileKey((k) => k + 1);
            },
        });
    }

    const akta = dokumenWajibStatus?.akta;
    const izin = dokumenWajibStatus?.izin;

    return (
        <>
            {/* Status Dokumen Instansi Wajib */}
            <div className="border-b border-[#e7eadc] bg-[#f9faf5] px-6 py-4">
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-[#636c5b]">
                    Status Dokumen Legalitas Wajib (Syarat Bantuan):
                </p>
                <div className="grid gap-3 sm:grid-cols-2">
                    <div
                        className={`rounded-xl border p-3 flex items-center gap-3 ${
                            akta?.ada && !akta?.kadaluarsa
                                ? 'border-emerald-200 bg-emerald-50/70 text-emerald-950'
                                : akta?.kadaluarsa
                                ? 'border-rose-200 bg-rose-50 text-rose-950'
                                : 'border-amber-200 bg-amber-50 text-amber-950'
                        }`}
                    >
                        <div
                            className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${
                                akta?.ada && !akta?.kadaluarsa ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'
                            }`}
                        >
                            <Icon name={akta?.ada && !akta?.kadaluarsa ? 'check' : 'alert'} size={16} />
                        </div>
                        <div className="min-w-0">
                            <p className="font-bold text-xs uppercase tracking-wide">Akta Notaris / SK Pendirian</p>
                            <p className="text-xs font-medium">
                                {akta?.ada && !akta?.kadaluarsa
                                    ? '✓ Dokumen Lengkap & Terverifikasi'
                                    : akta?.kadaluarsa
                                    ? '⚠ Masa Berlaku Telah Kedaluwarsa'
                                    : 'Belum Ada (Wajib Diunggah)'}
                            </p>
                        </div>
                    </div>

                    <div
                        className={`rounded-xl border p-3 flex items-center gap-3 ${
                            izin?.ada && !izin?.kadaluarsa
                                ? 'border-emerald-200 bg-emerald-50/70 text-emerald-950'
                                : izin?.kadaluarsa
                                ? 'border-rose-200 bg-rose-50 text-rose-950'
                                : 'border-amber-200 bg-amber-50 text-amber-950'
                        }`}
                    >
                        <div
                            className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${
                                izin?.ada && !izin?.kadaluarsa ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'
                            }`}
                        >
                            <Icon name={izin?.ada && !izin?.kadaluarsa ? 'check' : 'alert'} size={16} />
                        </div>
                        <div className="min-w-0">
                            <p className="font-bold text-xs uppercase tracking-wide">Izin Operasional Dinsos</p>
                            <p className="text-xs font-medium">
                                {izin?.ada && !izin?.kadaluarsa
                                    ? '✓ Izin Masih Aktif & Berlaku'
                                    : izin?.kadaluarsa
                                    ? '⚠ Izin Operasional Kedaluwarsa'
                                    : 'Belum Ada (Wajib Diunggah)'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {can.manage && (
                <form onSubmit={upload} className="grid gap-4.5 border-b border-[#e7eadc] bg-[#fbfcf8] p-6 md:grid-cols-2">
                    <Field label="Jenis Dokumen Instansi" required error={form.errors.jenis} hint="Pilih kategori berkas legalitas">
                        <select value={form.data.jenis} onChange={(e) => form.setData('jenis', e.target.value)} className={inputClass}>
                            <option value="">-- Pilih Jenis Dokumen --</option>
                            {Object.entries(options.jenis || {}).map(([v, l]) => (
                                <option key={v} value={v}>
                                    {l}
                                </option>
                            ))}
                        </select>
                    </Field>
                    <Field label="Judul / Nama Dokumen" required error={form.errors.judul} hint="Contoh: Surat Izin Operasional Dinsos 2026">
                        <input
                            value={form.data.judul}
                            onChange={(e) => form.setData('judul', e.target.value)}
                            placeholder="Ketikkan judul dokumen..."
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Nomor Surat / SK Dokumen" error={form.errors.nomor} hint="Nomor resmi yang tercantum pada surat/akta">
                        <input
                            value={form.data.nomor}
                            onChange={(e) => form.setData('nomor', e.target.value)}
                            placeholder="Contoh: 503/IO/DINSOS/2026"
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Masa Berlaku Sampai" error={form.errors.berlaku_sampai} hint="Kosongkan jika dokumen berlaku tetap/tanpa batas waktu">
                        <input
                            type="date"
                            value={form.data.berlaku_sampai}
                            onChange={(e) => form.setData('berlaku_sampai', e.target.value)}
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Berkas Dokumen (File)" required error={form.errors.file} hint="Format berkas: PDF, JPG, PNG, DOC, DOCX. Maksimal ukuran 5 MB." className="md:col-span-2">
                        <input
                            key={fileKey}
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            onChange={(e) => form.setData('file', e.target.files[0] ?? null)}
                            className={`${inputClass} file:mr-3.5 file:rounded-lg file:border-0 file:bg-[#eef1e6] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[#203b2e] file:cursor-pointer`}
                        />
                    </Field>
                    <div className="md:col-span-2 pt-1">
                        <button type="submit" disabled={form.processing} className={btnPrimary}>
                            <Icon name="upload" size={16} />
                            {form.processing ? `Mengunggah Berkas (${form.progress?.percentage ?? 0}%)...` : 'Unggah Dokumen Instansi'}
                        </button>
                    </div>
                </form>
            )}

            <table className="w-full text-sm">
                <thead>
                    <tr>
                        <th className={thClass}>Nama Dokumen & Nomor</th>
                        <th className={thClass}>Jenis Dokumen</th>
                        <th className={thClass}>Masa Berlaku</th>
                        <th className={thClass}>Ukuran Berkas</th>
                        <th className={thClass}>Aksi Unduh / Kelola</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {dokumens.length === 0 ? (
                        <EmptyRow colSpan={5}>Belum ada dokumen instansi yang diunggah.</EmptyRow>
                    ) : (
                        dokumens.map((d) => (
                            <tr key={d.id} className="hover:bg-[#fbfcf9] transition">
                                <td className={tdClass}>
                                    <p className="font-bold text-[#1e2f24] text-base">{d.judul}</p>
                                    {d.nomor && <p className="text-xs font-medium text-[#656d5e]">No: {d.nomor}</p>}
                                    <p className="text-xs text-[#959b8d] mt-0.5">Diunggah: {formatDate(d.created_at)}</p>
                                </td>
                                <td className={tdClass}>
                                    <span className="inline-block rounded-md bg-[#eef1e6] px-2.5 py-1 text-xs font-bold text-[#203b2e]">
                                        {options.jenis?.[d.jenis] ?? d.jenis}
                                    </span>
                                </td>
                                <td className={tdClass}>
                                    {d.berlaku_sampai ? (
                                        <div>
                                            <span className="text-xs font-bold text-[#1f2e24]">
                                                {formatDate(d.berlaku_sampai)}
                                            </span>
                                            {d.kadaluarsa && (
                                                <p className="mt-1">
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-rose-100 px-2 py-0.5 text-xs font-extrabold text-rose-700">
                                                        <Icon name="alert" size={12} /> Kedaluwarsa
                                                    </span>
                                                </p>
                                            )}
                                        </div>
                                    ) : (
                                        <span className="text-xs font-semibold text-[#737c6d]">Berlaku Tetap</span>
                                    )}
                                </td>
                                <td className={`${tdClass} text-xs font-medium text-[#6c7465]`}>
                                    {formatBytes(d.ukuran)}
                                </td>
                                <td className={tdClass}>
                                    <div className="flex gap-2">
                                        <a href={`/dokumen/${d.id}/unduh`} className={actView}>
                                            <Icon name="download" size={14} /> Unduh
                                        </a>
                                        {can.manage && (
                                            <button
                                                type="button"
                                                onClick={() => onRequestDelete('dokumen', d.id, d.judul)}
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
        </>
    );
}

