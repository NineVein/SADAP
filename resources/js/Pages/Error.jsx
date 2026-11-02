import { Head, Link } from '@inertiajs/react';

const messages = {
    403: ['Akses ditolak', 'Akun Anda tidak memiliki izin untuk membuka halaman ini.'],
    404: ['Halaman tidak ditemukan', 'Alamat yang Anda buka tidak ada atau datanya sudah dihapus.'],
    419: ['Sesi berakhir', 'Muat ulang halaman lalu coba lagi.'],
    500: ['Terjadi kesalahan', 'Ada masalah di server. Coba lagi beberapa saat atau hubungi administrator.'],
    503: ['Sedang pemeliharaan', 'Sistem sedang dipelihara. Silakan kembali beberapa saat lagi.'],
};

export default function Error({ status }) {
    const [title, text] = messages[status] ?? messages[500];

    return (
        <>
            <Head title={`${status} - SADAP`} />
            <main className="flex min-h-screen items-center justify-center bg-[#f6f6f1] px-4">
                <div className="max-w-md text-center">
                    <p className="text-6xl font-bold text-[#b49550]">{status}</p>
                    <h1 className="mt-4 text-2xl font-bold text-[#203b2e]">{title}</h1>
                    <p className="mt-2 text-sm text-[#70786d]">{text}</p>
                    <Link href="/" className="mt-6 inline-flex rounded-lg bg-[#203b2e] px-5 py-2.5 text-sm font-semibold text-[#f7f2e5] hover:bg-[#2b4d3c]">
                        Kembali ke dashboard
                    </Link>
                </div>
            </main>
        </>
    );
}
