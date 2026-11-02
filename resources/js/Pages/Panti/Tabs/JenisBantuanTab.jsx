import { useState } from 'react';
import Icon from '../../../Components/Icon';
import pantiService from '../../../Services/pantiService';
import { formatDate, formatRupiah } from '../../../Utils/format';
import { EmptyRow, btnPrimary, inputClass, tdClass, thClass } from '../../../Components/ui';

/**
 * Tab Jenis Bantuan Panti mengadopsi struktur bersih dari dinas JenisBantuanTab.
 */
export default function JenisBantuanTab({ panti, jenisBantuan = [], can = {} }) {
    const [items, setItems] = useState(
        jenisBantuan.map((j) => ({
            id: j.id,
            kode: j.kode,
            nama: j.nama,
            kategori: j.kategori,
            deskripsi: j.deskripsi,
            dibutuhkan: Boolean(j.dibutuhkan),
            catatan: j.catatan || '',
            kali_diterima: j.kali_diterima || 0,
            total_diterima: j.total_diterima || 0,
            terakhir: j.terakhir || null,
        }))
    );

    const [saving, setSaving] = useState(false);

    function toggle(id, dibutuhkan) {
        setItems((prev) =>
            prev.map((item) => (item.id === id ? { ...item, dibutuhkan } : item))
        );
    }

    function setCatatan(id, catatan) {
        setItems((prev) =>
            prev.map((item) => (item.id === id ? { ...item, catatan } : item))
        );
    }

    function save() {
        setSaving(true);
        pantiService.simpanJenisBantuan(panti.id, items, {
            onFinish: () => setSaving(false),
        });
    }

    const totalDibutuhkan = items.filter((x) => x.dibutuhkan).length;
    const totalNilaiDiterima = items.reduce((acc, curr) => acc + (Number(curr.total_diterima) || 0), 0);

    return (
        <>
            <div className="border-b border-[#e7eadc] bg-[#f9faf5] px-6 py-4 flex flex-wrap items-center justify-between gap-3 text-xs text-[#525a4a]">
                <div className="flex items-center gap-2">
                    <Icon name="check" size={16} className="text-[#203b2e]" />
                    <span>
                        Centang jenis bantuan yang dibutuhkan oleh panti ini. Kolom kanan menunjukkan rekapitulasi bantuan yang telah diterima.
                    </span>
                </div>
                {totalNilaiDiterima > 0 && (
                    <span className="font-bold text-[#203b2e] bg-[#eef1e6] px-3 py-1 rounded-full">
                        Total Realisasi Bantuan: {formatRupiah(totalNilaiDiterima)}
                    </span>
                )}
            </div>

            <table className="w-full text-sm">
                <thead>
                    <tr>
                        <th className={`${thClass} w-14 text-center`}>Pilih</th>
                        <th className={thClass}>Jenis Kebutuhan Bantuan</th>
                        <th className={thClass}>Catatan Kebutuhan Spesifik</th>
                        <th className={thClass}>Riwayat Penyaluran Diterima</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {items.length === 0 ? (
                        <EmptyRow colSpan={4}>Belum ada master data jenis bantuan.</EmptyRow>
                    ) : (
                        items.map((r) => (
                            <tr
                                key={r.id}
                                className={`transition ${r.dibutuhkan ? 'bg-[#f4f7ee]' : 'hover:bg-[#fbfcf9]'}`}
                            >
                                <td className={`${tdClass} text-center`}>
                                    <input
                                        type="checkbox"
                                        disabled={!can.manage}
                                        checked={r.dibutuhkan}
                                        onChange={(e) => toggle(r.id, e.target.checked)}
                                        className="h-5 w-5 rounded border-[#c7ccbe] text-[#203b2e] focus:ring-[#203b2e] cursor-pointer"
                                    />
                                </td>
                                <td className={tdClass}>
                                    <label
                                        onClick={() => can.manage && toggle(r.id, !r.dibutuhkan)}
                                        className="font-bold text-[#1e2f24] text-base cursor-pointer hover:text-[#203b2e]"
                                    >
                                        {r.nama}
                                    </label>
                                    <div className="mt-1">
                                        <span className="inline-block rounded-full bg-[#eef1e6] px-2.5 py-0.5 text-xs font-bold text-[#203b2e]">
                                            {r.kategori}
                                        </span>
                                    </div>
                                    {r.deskripsi && <p className="mt-1.5 text-xs text-[#6e7667] leading-relaxed">{r.deskripsi}</p>}
                                </td>
                                <td className={tdClass}>
                                    <input
                                        type="text"
                                        disabled={!can.manage || !r.dibutuhkan}
                                        value={r.catatan}
                                        onChange={(e) => setCatatan(r.id, e.target.value)}
                                        placeholder={
                                            r.dibutuhkan
                                                ? 'Tuliskan spesifikasi kebutuhan (misal: beras 50kg/bulan, 20 pasang seragam)'
                                                : 'Centang kotak di samping untuk mencatat kebutuhan ini'
                                        }
                                        className={inputClass}
                                    />
                                </td>
                                <td className={`${tdClass} text-xs text-[#626a5c]`}>
                                    {r.kali_diterima > 0 ? (
                                        <div className="rounded-xl border border-[#e2e6d6] bg-white p-2.5">
                                            <p className="font-extrabold text-[#203b2e] text-sm">{r.kali_diterima}× Pernah Diterima</p>
                                            <p className="font-bold text-emerald-700 mt-0.5">{formatRupiah(r.total_diterima)}</p>
                                            {r.terakhir && <p className="text-[11px] text-[#868d7e] mt-1">Terakhir: {formatDate(r.terakhir)}</p>}
                                        </div>
                                    ) : (
                                        <span className="inline-block rounded-md bg-[#edf0e5] px-2 py-1 text-xs font-semibold text-[#737b6c]">
                                            Belum ada riwayat
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>

            {can.manage && items.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#edf0e5] bg-[#fbfcf9] px-6 py-4">
                    <p className="text-xs font-medium text-[#6c7465]">
                        <strong className="text-[#203b2e] font-bold">{totalDibutuhkan}</strong> dari total {items.length} jenis bantuan ditandai sebagai kebutuhan operasional panti ini.
                    </p>
                    <button
                        type="button"
                        onClick={save}
                        disabled={saving}
                        className={btnPrimary}
                    >
                        <Icon name="check" size={16} />
                        {saving ? 'Menyimpan...' : 'Simpan Kebutuhan Bantuan'}
                    </button>
                </div>
            )}
        </>
    );
}

