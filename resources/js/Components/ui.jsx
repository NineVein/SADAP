import { Link } from '@inertiajs/react';
import Icon from './Icon';

/* ---------- Kelas Style Bersama (Aksesibel & Nyaman untuk Semua Usia) ---------- */
// Input minimal 42-44px tinggi agar mudah disentuh/diklik oleh pengguna lanjut usia maupun di layar sentuh
export const inputClass =
    'w-full min-h-[44px] rounded-xl border border-[#d3d6c8] bg-white px-3.5 py-2.5 text-sm text-[#1e2a22] shadow-xs outline-none transition placeholder:text-[#9ea396] focus:border-[#203b2e] focus:ring-2 focus:ring-[#203b2e]/20 disabled:bg-[#f6f7f2] disabled:text-[#888e80] disabled:cursor-not-allowed';

const btnBase =
    'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#203b2e] active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 shadow-xs';

export const btnPrimary = `${btnBase} bg-[#203b2e] text-[#f7f2e5] hover:bg-[#2b4d3c] hover:shadow-md`;
export const btnSecondary = `${btnBase} border border-[#d3d6c8] bg-white text-[#203b2e] hover:bg-[#f3f4ec] hover:border-[#b8bdad]`;
export const btnDanger = `${btnBase} bg-rose-600 text-white hover:bg-rose-700 hover:shadow-md`;

const act = 'inline-flex min-h-[36px] items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition shadow-2xs';
export const actView = `${act} bg-[#eef1e6] text-[#203b2e] hover:bg-[#dfe6d3]`;
export const actEdit = `${act} bg-amber-50 text-amber-800 border border-amber-200/60 hover:bg-amber-100`;
export const actDelete = `${act} bg-rose-50 text-rose-700 border border-rose-200/60 hover:bg-rose-100`;

export const thClass = 'px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-[#63685c] bg-[#f9faf6] border-b border-[#e5e7dc]';
export const tdClass = 'px-5 py-4 align-top text-sm text-[#25352b]';

/* ---------- Format Data (Diekspor dari Utils/format untuk menghindari duplikasi) ---------- */
export { formatDate, formatDateTime, formatBytes, formatRupiah, rp, tgl, snakeToTitle } from '../Utils/format';

/* ---------- Komponen UI ---------- */

export function Breadcrumbs({ items = [] }) {
    if (!items.length) return null;
    return (
        <nav aria-label="Navigasi rekam jejak (Breadcrumb)" className="mb-3.5 flex items-center gap-2 text-xs font-medium text-[#73796b] print:hidden">
            <Link href="/" className="flex items-center gap-1 hover:text-[#203b2e] transition">
                <Icon name="dashboard" size={14} />
                <span>Beranda</span>
            </Link>
            {items.map((item, idx) => {
                const isLast = idx === items.length - 1;
                return (
                    <span key={idx} className="flex items-center gap-2">
                        <span className="text-[#b5bba9]">/</span>
                        {item.href && !isLast ? (
                            <Link href={item.href} className="hover:text-[#203b2e] transition">
                                {item.label}
                            </Link>
                        ) : (
                            <span className="font-bold text-[#1e2a22]">{item.label}</span>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}

export function PageHeader({ title, description, actions, breadcrumbs }) {
    return (
        <div className="mb-7">
            {breadcrumbs && <Breadcrumbs items={breadcrumbs} />}
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#1b2e23]">{title}</h1>
                    {description && <p className="mt-1.5 text-sm sm:text-base text-[#5c6355] leading-relaxed max-w-3xl">{description}</p>}
                </div>
                {actions && <div className="flex flex-wrap items-center gap-2.5 print:hidden">{actions}</div>}
            </div>
        </div>
    );
}

export function Card({ title, description, actions, children, padded = true, className = '' }) {
    return (
        <section className={`rounded-2xl border border-[#e2e4d8] bg-white shadow-xs overflow-hidden ${className}`}>
            {(title || actions) && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#eef0e4] bg-[#fbfcf9] px-6 py-4.5">
                    <div>
                        {title && <h2 className="text-base font-bold text-[#1b2e23]">{title}</h2>}
                        {description && <p className="mt-0.5 text-xs sm:text-sm text-[#6c7365]">{description}</p>}
                    </div>
                    {actions}
                </div>
            )}
            <div className={padded ? 'p-6' : ''}>{children}</div>
        </section>
    );
}

const badgeConfig = {
    aktif: { style: 'bg-emerald-50 text-emerald-800 border-emerald-200', icon: 'check', text: 'Aktif' },
    nonaktif: { style: 'bg-slate-100 text-slate-700 border-slate-200', icon: 'close', text: 'Nonaktif' },
    keluar: { style: 'bg-slate-100 text-slate-700 border-slate-200', icon: 'close', text: 'Keluar' },
    draft: { style: 'bg-slate-100 text-slate-700 border-slate-200', icon: 'file', text: 'Draft (Belum Diajukan)' },
    diajukan: { style: 'bg-amber-50 text-amber-800 border-amber-300', icon: 'clock', text: 'Menunggu Validasi' },
    tervalidasi: { style: 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold', icon: 'check', text: 'Tervalidasi' },
    ditolak: { style: 'bg-rose-50 text-rose-800 border-rose-300 font-bold', icon: 'alert', text: 'Ditolak' },
};

export function StatusBadge({ value, label }) {
    const config = badgeConfig[value] ?? {
        style: 'bg-slate-100 text-slate-700 border-slate-200',
        icon: null,
        text: value,
    };

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold ${config.style}`}>
            {config.icon && <Icon name={config.icon} size={13} />}
            <span>{label ?? config.text}</span>
        </span>
    );
}

export function ProgressBar({ value }) {
    const isFull = value === 100;
    const color = isFull ? 'bg-emerald-500' : value >= 60 ? 'bg-[#b49550]' : 'bg-rose-500';

    return (
        <div className="flex items-center gap-3">
            <div className="h-2 w-28 overflow-hidden rounded-full bg-[#eaece0]">
                <div className={`h-full rounded-full transition-all duration-500 ${color}`} style={{ width: `${value}%` }} />
            </div>
            <span className={`text-xs font-bold ${isFull ? 'text-emerald-700' : 'text-[#5c6355]'}`}>
                {value}% {isFull && '✓'}
            </span>
        </div>
    );
}

export function Field({ label, error, hint, required, children, className = '' }) {
    return (
        <div className={className}>
            <label className="mb-1.5 block text-sm font-bold text-[#1f2937]">
                {label}
                {required && <span className="ml-1 text-rose-600 font-bold">*</span>}
            </label>
            {children}
            {hint && !error && <p className="mt-1.5 text-xs text-[#636a5c] leading-normal">{hint}</p>}
            {error && (
                <p className="mt-1.5 flex items-center gap-1 text-xs font-semibold text-rose-700">
                    <Icon name="alert" size={13} />
                    <span>{error}</span>
                </p>
            )}
        </div>
    );
}

export function EmptyRow({ colSpan, children, onReset }) {
    return (
        <tr>
            <td colSpan={colSpan} className="px-6 py-14 text-center text-[#6c7365]">
                <div className="mx-auto flex max-w-md flex-col items-center justify-center">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#edf0e4] text-[#555d4f] shadow-inner">
                        <Icon name="search" size={26} />
                    </div>
                    <p className="mt-4 text-base font-bold text-[#203b2e]">{children}</p>
                    <p className="mt-1 text-xs text-[#7e8576]">Data tidak ditemukan dengan kriteria pencarian saat ini.</p>
                    {onReset && (
                        <button
                            type="button"
                            onClick={onReset}
                            className="mt-4 inline-flex items-center gap-2 rounded-xl border border-[#d3d6c8] bg-white px-4 py-2 text-xs font-bold text-[#203b2e] hover:bg-[#f3f4ec] shadow-xs"
                        >
                            <Icon name="close" size={14} /> Bersihkan Filter Pencarian
                        </button>
                    )}
                </div>
            </td>
        </tr>
    );
}

const pageLabel = (label) =>
    label.replace('&laquo; Previous', '← Sebelumnya').replace('Next &raquo;', 'Berikutnya →');

export function Pagination({ paginator }) {
    if (!paginator) return null;
    return (
        <div className="flex flex-wrap items-center justify-between gap-4 border-t border-[#eef0e4] px-6 py-4.5 bg-[#fbfcf9] print:hidden">
            <p className="text-xs sm:text-sm font-medium text-[#656c5e]">
                {paginator.total > 0 ? (
                    <>
                        Menampilkan <strong className="text-[#1b2e23]">{paginator.from}</strong>–
                        <strong className="text-[#1b2e23]">{paginator.to}</strong> dari total{' '}
                        <strong className="text-[#1b2e23]">{paginator.total}</strong> data
                    </>
                ) : (
                    'Tidak ada data untuk ditampilkan'
                )}
            </p>
            {paginator.last_page > 1 && (
                <div className="flex flex-wrap gap-1.5">
                    {paginator.links.map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                preserveScroll
                                preserveState
                                className={`min-h-[38px] min-w-[38px] flex items-center justify-center rounded-xl border px-3 text-xs font-bold transition ${
                                    link.active
                                        ? 'border-[#203b2e] bg-[#203b2e] text-white shadow-xs'
                                        : 'border-[#d3d6c8] bg-white text-[#2a382e] hover:bg-[#f3f4ec]'
                                }`}
                            >
                                {pageLabel(link.label)}
                            </Link>
                        ) : (
                            <span key={i} className="min-h-[38px] min-w-[38px] flex items-center justify-center rounded-xl border border-[#eef0e4] bg-[#f9faf6] px-3 text-xs text-[#a8ad9f]">
                                {pageLabel(link.label)}
                            </span>
                        )
                    )}
                </div>
            )}
        </div>
    );
}

export function FilterBar({ children, onReset, hasActiveFilters = false }) {
    return (
        <div className="mb-6 rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-xs print:hidden">
            <div className="flex items-center gap-2 mb-3 pb-2.5 border-b border-[#f0f2e8]">
                <Icon name="search" size={16} className="text-[#203b2e]" />
                <span className="text-xs font-bold uppercase tracking-wider text-[#52594c]">Pencarian & Penyaringan Data</span>
            </div>
            <div className="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                {children}
            </div>
            {hasActiveFilters && onReset && (
                <div className="mt-4 flex justify-end border-t border-[#f0f2e8] pt-3">
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/60 hover:bg-rose-100 transition"
                    >
                        <Icon name="close" size={14} /> Bersihkan Semua Filter
                    </button>
                </div>
            )}
        </div>
    );
}

/* ---------- Modal Konfirmasi Interaktif Modern & Nyaman ---------- */
export function ConfirmModal({
    isOpen,
    onClose,
    onConfirm,
    title = 'Konfirmasi Tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    confirmText = 'Ya, Lanjutkan',
    cancelText = 'Batal',
    danger = true,
    processing = false,
}) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden animate-fade-in">
            {/* Backdrop dengan efek blur lembut */}
            <div className="absolute inset-0 bg-black/50 backdrop-blur-xs transition-opacity" onClick={onClose} />

            {/* Modal Dialog */}
            <div
                role="dialog"
                aria-modal="true"
                className="relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl border border-[#e2e4d8] transition-all"
            >
                <div className="flex items-start gap-4">
                    <div
                        className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${
                            danger ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'
                        }`}
                    >
                        <Icon name={danger ? 'trash' : 'alert'} size={24} />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h3 className="text-lg font-bold text-[#1b2e23]">{title}</h3>
                        <p className="mt-2 text-sm leading-relaxed text-[#5c6355]">{message}</p>
                    </div>
                </div>

                <div className="mt-6 flex items-center justify-end gap-3 border-t border-[#f0f2e8] pt-4">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className={btnSecondary}
                    >
                        {cancelText}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className={danger ? btnDanger : btnPrimary}
                    >
                        {processing ? 'Memproses...' : confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
}
