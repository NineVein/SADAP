import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Icon from '../../../Components/Icon';
import Badge from '../../../Components/Badge';
import { JABATAN } from '../../../Config/constants';
import { formatDate } from '../../../Utils/format';
import {
    Field, EmptyRow, actDelete, actEdit, btnDanger, btnPrimary, btnSecondary,
    inputClass, tdClass, thClass,
} from '../../../Components/ui';

/**
 * Tab Pengurus Panti mengadopsi struktur bersih dari dinas PengurusTab.
 */
export default function PengurusTab({ panti, pengurus = [], can = {}, openKey, onRequestDelete }) {
    const [mode, setMode] = useState(null); // null | 'baru' | id
    const form = useForm({
        panti_id: panti.id,
        nama: '',
        jabatan: '',
        telepon: '',
        email: '',
        alamat: '',
        periode_mulai: '',
        periode_selesai: '',
        status: 'aktif',
    });

    useEffect(() => {
        if (openKey) startNew();
    }, [openKey]);

    function startNew() {
        form.reset();
        form.clearErrors();
        form.setData({
            panti_id: panti.id,
            nama: '',
            jabatan: '',
            telepon: '',
            email: '',
            alamat: '',
            periode_mulai: '',
            periode_selesai: '',
            status: 'aktif',
        });
        setMode('baru');
    }

    function startEdit(p) {
        form.clearErrors();
        form.setData({
            panti_id: panti.id,
            nama: p.nama,
            jabatan: p.jabatan,
            telepon: p.telepon ?? '',
            email: p.email ?? '',
            alamat: p.alamat ?? '',
            periode_mulai: p.periode_mulai ? p.periode_mulai.substring(0, 10) : '',
            periode_selesai: p.periode_selesai ? p.periode_selesai.substring(0, 10) : '',
            status: p.status ?? 'aktif',
        });
        setMode(p.id);
    }

    function submit(e) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setMode(null) };
        mode === 'baru' ? form.post('/pengurus', opts) : form.put(`/pengurus/${mode}`, opts);
    }

    return (
        <>
            {/* Banner Edukasi Aturan Kepengurusan */}
            <div className="border-b border-[#e7eadc] bg-[#f9faf5] px-6 py-3.5 flex items-center gap-2.5 text-xs text-[#525a4a]">
                <Icon name="shield" size={16} className="text-[#203b2e] shrink-0" />
                <span>
                    <strong>Aturan Jabatan Inti:</strong> Jabatan Ketua, Sekretaris, dan Bendahara masing-masing hanya boleh diisi oleh 1 orang berstatus 'Aktif' pada saat bersamaan.
                </span>
            </div>

            {mode !== null && (
                <form onSubmit={submit} className="grid gap-4.5 border-b border-[#e7eadc] bg-[#fbfcf8] p-6 md:grid-cols-2">
                    <Field label="Nama Lengkap Pengurus" required error={form.errors.nama} hint="Nama lengkap berserta gelar bila ada">
                        <input
                            value={form.data.nama}
                            onChange={(e) => form.setData('nama', e.target.value)}
                            placeholder="Contoh: Dra. Maria Sondakh"
                            className={inputClass}
                            autoFocus
                        />
                    </Field>
                    <Field label="Jabatan di Panti" required error={form.errors.jabatan} hint="Pilih dari daftar atau ketikkan jabatan baru">
                        <input
                            list="daftar-jabatan"
                            value={form.data.jabatan}
                            onChange={(e) => form.setData('jabatan', e.target.value)}
                            placeholder="Pilih atau ketik jabatan..."
                            className={inputClass}
                        />
                        <datalist id="daftar-jabatan">
                            {JABATAN.map((j) => (
                                <option key={j} value={j} />
                            ))}
                        </datalist>
                    </Field>
                    <Field label="Status Keaktifan" error={form.errors.status} hint="Pengurus nonaktif tetap tersimpan di riwayat">
                        <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className={inputClass}>
                            <option value="aktif">Aktif Menjabat</option>
                            <option value="nonaktif">Nonaktif / Demisioner</option>
                        </select>
                    </Field>
                    <Field label="Nomor Telepon / WhatsApp" error={form.errors.telepon} hint="Nomor kontak yang dapat dihubungi">
                        <input
                            value={form.data.telepon}
                            onChange={(e) => form.setData('telepon', e.target.value)}
                            placeholder="Contoh: 081234567890"
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Alamat Email" error={form.errors.email} hint="Alamat email resmi atau pribadi">
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            placeholder="Contoh: maria@panti.org"
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Periode Mulai Menjabat" error={form.errors.periode_mulai} hint="Tanggal SK atau awal masa bakti">
                        <input
                            type="date"
                            value={form.data.periode_mulai}
                            onChange={(e) => form.setData('periode_mulai', e.target.value)}
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Periode Selesai Menjabat" error={form.errors.periode_selesai} hint="Kosongkan jika masih aktif menjabat sampai sekarang">
                        <input
                            type="date"
                            value={form.data.periode_selesai}
                            onChange={(e) => form.setData('periode_selesai', e.target.value)}
                            className={inputClass}
                        />
                    </Field>
                    <Field label="Alamat Domisili" error={form.errors.alamat} className="md:col-span-2">
                        <input
                            value={form.data.alamat}
                            onChange={(e) => form.setData('alamat', e.target.value)}
                            placeholder="Alamat tempat tinggal lengkap..."
                            className={inputClass}
                        />
                    </Field>
                    <div className="flex gap-2.5 md:col-span-2 pt-2">
                        <button type="submit" disabled={form.processing} className={btnPrimary}>
                            <Icon name="check" size={16} />
                            {mode === 'baru' ? 'Simpan Data Pengurus' : 'Simpan Perubahan'}
                        </button>
                        <button type="button" onClick={() => setMode(null)} className={btnSecondary}>
                            Batal
                        </button>
                    </div>
                </form>
            )}

            <table className="w-full text-sm">
                <thead>
                    <tr>
                        <th className={thClass}>Nama & Jabatan</th>
                        <th className={thClass}>Masa Bakti / Periode</th>
                        <th className={thClass}>Status</th>
                        <th className={thClass}>Kontak Person</th>
                        {can.manage && <th className={thClass}>Aksi Tindakan</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {pengurus.length === 0 ? (
                        <EmptyRow colSpan={5}>Belum ada data pengurus yang dicatat untuk panti ini.</EmptyRow>
                    ) : (
                        pengurus.map((p) => {
                            const periodeText = p.periode_mulai
                                ? `${formatDate(p.periode_mulai)} s/d ${p.periode_selesai ? formatDate(p.periode_selesai) : 'Sekarang'}`
                                : '-';
                            return (
                                <tr key={p.id} className="hover:bg-[#fbfcf9] transition">
                                    <td className={tdClass}>
                                        <p className="font-bold text-[#1e2f24] text-base">{p.nama}</p>
                                        <span className="inline-block mt-0.5 rounded-full bg-[#eef1e6] px-2.5 py-0.5 text-xs font-semibold text-[#203b2e]">
                                            {p.jabatan}
                                        </span>
                                    </td>
                                    <td className={`${tdClass} text-xs font-medium text-[#656c5e]`}>{periodeText}</td>
                                    <td className={tdClass}>
                                        <Badge s={p.status || 'aktif'} />
                                    </td>
                                    <td className={`${tdClass} text-xs`}>
                                        <p className="font-bold text-[#203126]">{p.telepon || '-'}</p>
                                        {p.email && <p className="text-[#6c7465]">{p.email}</p>}
                                    </td>
                                    {can.manage && (
                                        <td className={tdClass}>
                                            <div className="flex gap-2">
                                                <button type="button" onClick={() => startEdit(p)} className={actEdit}>
                                                    Edit
                                                </button>
                                                <button type="button" onClick={() => onRequestDelete('pengurus', p.id, p.nama)} className={actDelete}>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            );
                        })
                    )}
                </tbody>
            </table>

            {can.manage && mode === null && (
                <div className="border-t border-[#edf0e5] bg-[#fbfcf9] px-6 py-4">
                    <button type="button" onClick={startNew} className={btnPrimary}>
                        <Icon name="plus" size={16} /> Tambah Pengurus Baru
                    </button>
                </div>
            )}
        </>
    );
}

