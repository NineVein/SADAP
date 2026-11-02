import { STATUS_LABELS } from '../Config/constants';

const COLOR_MAP = {
    draft: 'bg-[#edf0e5] text-[#555d4e] border-[#d5dacb]',
    diajukan: 'bg-amber-100/90 text-amber-900 border-amber-300',
    menunggu_verifikasi: 'bg-amber-100/90 text-amber-900 border-amber-300',
    perlu_perbaikan: 'bg-amber-100 text-amber-900 border-amber-400',
    terverifikasi: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    tervalidasi: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    ditetapkan: 'bg-teal-100 text-teal-900 border-teal-300',
    dalam_penyaluran: 'bg-sky-100 text-sky-900 border-sky-300',
    selesai: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    ditolak: 'bg-rose-100 text-rose-900 border-rose-300',
    aktif: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    nonaktif: 'bg-[#edf0e5] text-[#6b7264] border-[#d3d8c8]',
    layak: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    bermasalah: 'bg-rose-100 text-rose-900 border-rose-300',
    dikirim: 'bg-sky-100 text-sky-900 border-sky-300',
    diterima: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    tinggi: 'bg-rose-100 text-rose-900 border-rose-300',
    sedang: 'bg-amber-100 text-amber-900 border-amber-300',
    rendah: 'bg-emerald-100 text-emerald-900 border-emerald-300',
};

/**
 * Komponen Badge universal mengadopsi pola Badge dinas.
 * Menghilangkan perulangan kode badge status dan menjaga konsistensi UI.
 */
export default function Badge({ s, label }) {
    if (!s && !label) return <span className="text-xs text-[#909789]">-</span>;

    const raw = String(s || '').toLowerCase();
    const style = COLOR_MAP[raw] || 'bg-[#f0f2eb] text-[#3e473b] border-[#d7ddce]';
    const text = label || STATUS_LABELS[raw] || String(s).replaceAll('_', ' ');

    return (
        <span
            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold capitalize tracking-wide transition ${style}`}
        >
            {text}
        </span>
    );
}

