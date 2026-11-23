import { KATEGORI_KLIEN } from '../Config/constants';

/**
 * Tab pemisah kategori (Semua | Anak | Lanjut Usia | Disabilitas) dengan jumlah.
 * Dipakai di halaman LKS dan Klien agar tiga jenis layanan tidak tercampur.
 */
export default function KategoriTabs({ value, onChange, counts = {}, allLabel = 'Semua Kategori' }) {
    const tabs = [{ key: '', label: allLabel, count: counts.semua }].concat(
        Object.entries(KATEGORI_KLIEN).map(([key, label]) => ({ key, label, count: counts[key] }))
    );

    return (
        <div role="tablist" aria-label="Kategori" className="mb-5 flex flex-wrap gap-2">
            {tabs.map((t) => {
                const active = (value ?? '') === t.key;
                return (
                    <button
                        key={t.key || 'semua'}
                        role="tab"
                        aria-selected={active}
                        type="button"
                        onClick={() => onChange(t.key)}
                        className={`flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition ${
                            active
                                ? 'border-[#b08935] bg-[#fdf8ed] text-[#7a5d1c] shadow-xs'
                                : 'border-[#d6d9cb] bg-white text-[#38433a] hover:bg-[#f1f3e9]'
                        }`}
                    >
                        <span>{t.label}</span>
                        {t.count !== undefined && (
                            <span className={`rounded-full px-2 py-0.5 text-[11px] font-extrabold ${active ? 'bg-[#b08935] text-white' : 'bg-[#eef0e6] text-[#6a7263]'}`}>
                                {t.count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
