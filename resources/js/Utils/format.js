// Utilitas murni untuk pemformatan teks, angka, mata uang, dan tanggal.

/**
 * Format angka ke mata uang Rupiah ringkas atau standar (Rp 1.000.000).
 * Mengadopsi helper rp() dari dinas.
 */
export const rp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
export const formatRupiah = rp;

/**
 * Format tanggal YYYY-MM-DD ringkas.
 * Mengadopsi helper tgl() dari dinas.
 */
export const tgl = (x) => (x ? String(x).slice(0, 10) : '-');

/**
 * Format tanggal ramah pengguna Bahasa Indonesia (contoh: 12 Okt 2026).
 */
export function formatDate(val) {
    if (!val) return '-';
    try {
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val).slice(0, 10);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Makassar',
        }).format(d);
    } catch {
        return String(val);
    }
}

/**
 * Format tanggal dan waktu ramah pengguna (contoh: 12 Okt 2026, 14:30 WITA).
 */
export function formatDateTime(val) {
    if (!val) return '-';
    try {
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Makassar',
        }).format(d);
    } catch {
        return String(val);
    }
}

/**
 * Format ukuran berkas bytes ke KB / MB.
 */
export function formatBytes(bytes) {
    const num = Number(bytes);
    if (!num || num <= 0) return '-';
    if (num < 1024) return `${num} B`;
    if (num < 1024 * 1024) return `${(num / 1024).toFixed(1)} KB`;
    return `${(num / (1024 * 1024)).toFixed(2)} MB`;
}

/**
 * Ratakan relasi bertingkat (panti.nama, pengajuan.nomor) untuk tabel datar.
 * Mengadopsi helper ratakanRelasi() dari dinas.
 */
export const ratakanRelasi = (rows) =>
    rows?.map((r) => ({
        ...r,
        panti_nama: r.panti?.nama ?? r.panti,
        pengajuan_nomor: r.pengajuan?.nomor ?? r.pengajuan,
    })) ?? [];

/**
 * Ubah string snake_case ke format berjarak yang rapi.
 */
export const snakeToTitle = (str) =>
    String(str || '-')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());

