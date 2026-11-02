import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import Icon from '../Components/Icon';
import { Card, StatusBadge, formatDate } from '../Components/ui';

function StatCard({ label, value, detail, icon, tone, href }) {
    // Menggunakan palet hex kustom untuk mempertahankan identitas warna
    const tones = {
        green: 'bg-[#edf1e4] text-[#203b2e] border-[#c4c8b9]',
        gold:  'bg-[#fdf8ed] text-[#b08935] border-[#d6b66d]',
        ink:   'bg-[#f7f8f3] text-[#454f40] border-[#e2e4d8]',
        rose:  'bg-[#fbecec] text-[#b33a3a] border-[#e3a1a1]',
    };

    return (
        <Link
            href={href}
            className="group rounded-2xl border border-[#e2e4d8] bg-white p-6 transition-all hover:border-[#203b2e] hover:shadow-md shadow-sm"
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-bold text-[#5a6253]">{label}</p>
                <span className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border ${tones[tone]} shadow-sm`}>
                    <Icon name={icon} size={22} />
                </span>
            </div>
            <p className="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-[#172b25]">{value}</p>
            <p className="mt-1.5 text-xs font-semibold text-[#5a6253] leading-relaxed">{detail}</p>
        </Link>
    );
}

const taskTone = {
    gold:  'bg-[#fdf8ed] text-[#b08935] border-[#d6b66d]',
    rose:  'bg-[#fbecec] text-[#b33a3a] border-[#e3a1a1]',
    green: 'bg-[#edf1e4] text-[#172b25] border-[#c4c8b9]',
    ink:   'bg-[#f7f8f3] text-[#454f40] border-[#e2e4d8]',
};

export default function Dashboard({ stats, tugas = [], pantiTerbaru = [] }) {
    const user = usePage().props.auth.user;
    const isOperator = user.role === 'operator';
    const firstName = user.name.split(' ')[0];

    const cards = [
        { label: 'Total Panti Asuhan', value: stats.total, detail: `${stats.aktif} aktif operasional, ${stats.nonaktif} nonaktif`, icon: 'building', tone: 'ink', href: '/panti' },
        { label: 'Total Anak Asuh', value: stats.anak_aktif, detail: 'Anak asuh aktif terdaftar di panti', icon: 'users', tone: 'green', href: '/anak?status=aktif' },
        { label: 'Panti Tervalidasi', value: stats.tervalidasi, detail: 'Lolos verifikasi kelayakan Dinas Sosial', icon: 'check', tone: 'green', href: '/panti?validasi=tervalidasi' },
        isOperator
            ? { label: 'Perlu Perbaikan', value: stats.ditolak, detail: 'Pengajuan yang perlu direvisi operator', icon: 'alert', tone: 'rose', href: '/panti?validasi=ditolak' }
            : { label: 'Menunggu Validasi', value: stats.diajukan, detail: 'Pengajuan masuk yang perlu dinilai reviewer', icon: 'clock', tone: 'gold', href: '/panti?validasi=diajukan' },
    ];

    return (
        <AppLayout>
            <Head title="Dashboard Utama - DISKATmsa" />

            <div className="space-y-8">
                {/* Hero Banner Sambutan */}
                <section className="relative isolate overflow-hidden rounded-3xl bg-[#172b25] text-white shadow-xl">
                    <div className="relative grid min-h-[220px] lg:grid-cols-[1.3fr_0.7fr]">
                        <div className="relative z-10 flex flex-col items-start justify-center px-6 py-9 sm:px-10 lg:px-12">
                            <span className="inline-block rounded-full bg-[#d6b66d]/20 border border-[#d6b66d]/40 px-3 py-1 text-xs font-bold text-[#e8c983]">
                                {user.role_label} {isOperator && user.kelurahan ? `· Wilayah ${user.kelurahan}` : ''}
                            </span>
                            <h1 className="mt-3.5 text-3xl font-extrabold leading-[1.2] tracking-tight !text-[#fbf8f2] sm:text-4xl lg:text-5xl">
                                Selamat bertugas,<br />
                                <span className="!text-[#d6b66d]">{firstName}</span>
                            </h1>
                            <p className="mt-3.5 text-sm sm:text-base text-[#d8dccf] max-w-xl leading-relaxed">
                                {tugas.length > 0
                                    ? `Terdapat ${tugas.length} agenda tugas yang membutuhkan tindak lanjut Anda hari ini.`
                                    : 'Seluruh data panti dan anak asuh telah lengkap dan tervalidasi dengan baik.'}
                            </p>
                        </div>

                        <div className="relative hidden overflow-hidden lg:block" aria-hidden="true">
                            {/* Gradient menggunakan hex palet khas */}
                            <div className="absolute inset-0 bg-gradient-to-br from-[#203b2e] via-[#172b25] to-[#0f1c18]" />
                            <div className="absolute -right-10 -top-16 h-[380px] w-[230px] rotate-[28deg] border-l border-[#d6b66d]/20" />
                            <div className="absolute bottom-0 right-0 h-[58%] w-[75%] border-l border-t border-[#d6b66d]/20" />
                        </div>
                    </div>
                    {/* Garis dekoratif bawah */}
                    <div className="h-1 bg-gradient-to-r from-[#d6b66d] via-[#e8c983] to-[#172b25]" />
                </section>

                {/* Ringkasan Statistik Kartu */}
                <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <StatCard key={card.label} {...card} />
                    ))}
                </section>

                {/* PUSAT TUGAS: Agenda Kerja yang Memerlukan Tindakan */}
                <Card
                    title="Pusat Tugas & Tindak Lanjut"
                    description="Klik langsung pada tugas di bawah untuk membuka halaman penyelesaian masalah"
                    padded={false}
                >
                    {tugas.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#edf1e4] text-[#203b2e] shadow-inner">
                                <Icon name="check" size={26} />
                            </span>
                            <p className="mt-4 text-base font-bold text-[#172b25]">Tidak Ada Tugas yang Tertunda</p>
                            <p className="mt-1 text-xs sm:text-sm text-[#5a6253]">
                                Seluruh berkas panti dan kelayakan anak binaan Anda dalam kondisi lengkap dan tervalidasi.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-[#e2e4d8]">
                            {tugas.map((t) => (
                                <li key={t.judul}>
                                    <Link
                                        href={t.href}
                                        className="flex items-center gap-4.5 px-6 py-4.5 transition hover:bg-[#f7f8f3]"
                                    >
                                        <span className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border text-xl font-extrabold shadow-inner ${taskTone[t.tone]}`}>
                                            {t.jumlah}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-base font-bold text-[#172b25]">{t.judul}</p>
                                            <p className="mt-0.5 text-xs sm:text-sm font-medium text-[#454f40]">{t.deskripsi}</p>
                                        </div>
                                        <Icon name="chevron" size={20} className="shrink-0 text-[#8a9182]" />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                {/* Panti Asuhan Terbaru */}
                <Card
                    title="Panti Asuhan yang Terakhir Terdaftar"
                    description="Data panti terbaru yang didaftarkan ke dalam sistem DISKATmsa"
                    padded={false}
                    actions={
                        <Link href="/panti" className="text-xs font-bold text-[#203b2e] hover:text-[#d6b66d] transition-colors hover:underline">
                            Lihat Semua Panti →
                        </Link>
                    }
                >
                    {pantiTerbaru.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <p className="text-sm text-[#5a6253]">Belum ada data panti yang terdaftar.</p>
                            <Link href="/panti/create" className="mt-3.5 inline-flex items-center gap-2 rounded-xl bg-[#203b2e] px-4 py-2.5 text-xs font-bold text-[#fbf8f2] hover:bg-[#172b25] shadow-sm transition-colors">
                                <Icon name="plus" size={15} /> Tambah Panti Baru
                            </Link>
                        </div>
                    ) : (
                        <ul className="divide-y divide-[#e2e4d8]">
                            {pantiTerbaru.map((panti) => (
                                <li key={panti.id}>
                                    <Link
                                        href={`/panti/${panti.id}`}
                                        className="flex flex-wrap items-center justify-between gap-4 px-6 py-4.5 transition hover:bg-[#f7f8f3]"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="text-base font-bold text-[#172b25]">{panti.nama}</p>
                                            <p className="mt-0.5 break-words text-xs font-medium text-[#5a6253]">{panti.alamat}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <StatusBadge value={panti.validation_status} />
                                            <span className="text-xs font-medium text-[#8a9182]">
                                                {formatDate(panti.created_at)}
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}