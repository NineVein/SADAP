import { KATEGORI_KLIEN } from '../Config/constants';

const STYLE = {
    anak: 'bg-sky-50 text-sky-800 border-sky-200',
    lanjut_usia: 'bg-amber-50 text-amber-900 border-amber-200',
    disabilitas: 'bg-violet-50 text-violet-800 border-violet-200',
};

/**
 * Lencana kategori LKS atau klien (Anak, Lanjut Usia, Disabilitas).
 * `prefix` contoh: "LKS" menghasilkan "LKS Anak".
 */
export default function KategoriBadge({ value, prefix = '', className = '' }) {
    if (!value) return null;

    return (
        <span
            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold ${STYLE[value] ?? 'bg-[#f0f2eb] text-[#3e473b] border-[#d7ddce]'} ${className}`}
        >
            {prefix ? `${prefix} ` : ''}{KATEGORI_KLIEN[value] ?? value}
        </span>
    );
}
