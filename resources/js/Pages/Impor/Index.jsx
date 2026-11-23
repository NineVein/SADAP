import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import {
    Card, Field, PageHeader, StatusBadge,
    btnPrimary, btnSecondary, inputClass, tdClass, thClass,
} from '../../Components/ui';

const JENIS = {
    lks: { label: 'Data LKS', hint: 'Daftar Lembaga Kesejahteraan Sosial' },
    klien: { label: 'Data Klien', hint: 'Klien yang tinggal di LKS (LKS harus sudah ada)' },
};

const STATUS = {
    baru: { value: 'tervalidasi', label: 'Siap disimpan' },
    duplikat: { value: 'diajukan', label: 'Duplikat, dilewati' },
    galat: { value: 'ditolak', label: 'Galat, tidak disimpan' },
};

const KOLOM_WAJIB = {
    lks: ['nama', 'kategori', 'alamat', 'kelurahan'],
    klien: ['nama_lks', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'latar_belakang'],
};

function Tile({ label, value, tone }) {
    const tones = {
        ink: 'bg-[#eef1e6] text-[#203b2e]',
        green: 'bg-emerald-50 text-emerald-800',
        gold: 'bg-amber-50 text-amber-800',
        rose: 'bg-rose-50 text-rose-700',
    };
    return (
        <div className={`rounded-xl px-5 py-4 ${tones[tone]}`}>
            <p className="text-3xl font-black">{value}</p>
            <p className="mt-0.5 text-xs font-bold">{label}</p>
        </div>
    );
}

export default function Index({ jenis, hasil, opsi, kolom, maks, isOperator }) {
    const form = useForm({ jenis, file: null });
    const [fileKey, setFileKey] = useState(0);
    const [menyimpan, setMenyimpan] = useState(false);

    function periksa(e) {
        e.preventDefault();
        form.setData('jenis', jenis);
        form.transform((d) => ({ ...d, jenis }));
        form.post('/impor/pratinjau', {
            forceFormData: true,
            onSuccess: () => { form.reset('file'); setFileKey((k) => k + 1); },
        });
    }

    function simpan() {
        router.post('/impor/simpan', { jenis, token: hasil.token }, {
            onStart: () => setMenyimpan(true),
            onFinish: () => setMenyimpan(false),
        });
    }

    const r = hasil?.ringkasan;

    return (
        <AppLayout>
            <Head title="Impor Data - DISKATmsa" />

            <PageHeader
                title="Impor Data dari CSV"
                description="Pindahkan data LKS dan klien yang sudah ada di Excel ke sistem. Setiap baris diperiksa dulu, duplikat dilewati, dan tidak ada yang tersimpan sebelum Anda mengonfirmasi."
                breadcrumbs={[{ label: 'Impor Data' }]}
            />

            <div role="tablist" className="mb-6 flex flex-wrap gap-2">
                {Object.entries(JENIS).map(([k, v]) => {
                    const active = k === jenis;
                    return (
                        <Link
                            key={k}
                            href={`/impor?jenis=${k}`}
                            role="tab"
                            aria-selected={active}
                            className={`rounded-xl border px-5 py-2.5 text-sm font-bold transition ${
                                active ? 'border-[#b08935] bg-[#fdf8ed] text-[#7a5d1c]' : 'border-[#d6d9cb] bg-white text-[#38433a] hover:bg-[#f1f3e9]'
                            }`}
                        >
                            {v.label}
                        </Link>
                    );
                })}
                <span className="self-center text-xs font-medium text-[#6c7465]">{JENIS[jenis].hint}</span>
            </div>

            <div className="grid items-start gap-6 xl:grid-cols-[1.4fr_1fr]">
                <div className="space-y-6">
                    <Card title="Langkah 1 · Unduh templat" description="Isi templat ini agar nama kolom sesuai. Baris contoh di dalamnya boleh dihapus.">
                        <a href={`/impor/templat/${jenis}`} className={btnSecondary}>
                            <Icon name="download" size={16} /> Unduh templat {JENIS[jenis].label} (CSV)
                        </a>
                        <p className="mt-4 text-xs font-bold text-[#525a4a]">Kolom pada templat</p>
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {kolom[jenis].map((c) => (
                                <span
                                    key={c}
                                    className={`rounded-md px-2 py-1 font-mono text-[11px] ${
                                        KOLOM_WAJIB[jenis].includes(c) ? 'bg-[#203b2e] text-[#f7f2e5]' : 'bg-[#eef1e6] text-[#3e473b]'
                                    }`}
                                    title={KOLOM_WAJIB[jenis].includes(c) ? 'Wajib diisi' : 'Boleh kosong'}
                                >
                                    {c}
                                </span>
                            ))}
                        </div>
                        <p className="mt-2 text-xs text-[#6c7465]">Kolom berwarna gelap wajib diisi. Dari Excel: <em>Simpan Sebagai</em> lalu pilih <em>CSV</em>.</p>
                    </Card>

                    <Card title="Langkah 2 · Unggah dan periksa" description={`CSV, maksimal ${maks.kb / 1024} MB dan ${maks.baris} baris per berkas.`}>
                        <form onSubmit={periksa} className="space-y-4">
                            <Field label="Berkas CSV" required error={form.errors.file ?? form.errors.jenis}>
                                <input
                                    key={fileKey}
                                    type="file"
                                    accept=".csv,.txt,text/csv"
                                    onChange={(e) => form.setData('file', e.target.files[0] ?? null)}
                                    className={`${inputClass} file:mr-3 file:rounded-md file:border-0 file:bg-[#eef1e6] file:px-3 file:py-1 file:text-xs file:font-bold file:text-[#203b2e]`}
                                />
                            </Field>
                            <button type="submit" disabled={form.processing || !form.data.file} className={btnPrimary}>
                                <Icon name="upload" size={16} />
                                {form.processing ? 'Memeriksa...' : 'Periksa Berkas'}
                            </button>
                        </form>
                    </Card>

                    {hasil && (
                        <Card
                            title="Langkah 3 · Hasil pemeriksaan"
                            description={`Berkas: ${hasil.nama_berkas}. Belum ada data yang tersimpan.`}
                            padded={false}
                        >
                            <div className="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4">
                                <Tile label="Total baris" value={r.total} tone="ink" />
                                <Tile label="Siap disimpan" value={r.baru} tone="green" />
                                <Tile label="Duplikat" value={r.duplikat} tone="gold" />
                                <Tile label="Galat" value={r.galat} tone="rose" />
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th className={thClass}>Baris</th>
                                            <th className={thClass}>Data</th>
                                            <th className={thClass}>Hasil</th>
                                            <th className={thClass}>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#edf0e5]">
                                        {hasil.baris.map((b) => (
                                            <tr key={b.baris} className="hover:bg-[#fbfcf9]">
                                                <td className={`${tdClass} font-mono text-xs`}>{b.baris}</td>
                                                <td className={tdClass}>
                                                    <p className="font-bold text-[#1b2e23]">{b.ringkas[0] || '-'}</p>
                                                    <p className="text-xs text-[#757e70]">{b.ringkas.slice(1).filter(Boolean).join(' · ')}</p>
                                                </td>
                                                <td className={tdClass}><StatusBadge value={STATUS[b.status].value} label={STATUS[b.status].label} /></td>
                                                <td className={tdClass}>
                                                    {b.pesan.length === 0 ? (
                                                        <span className="text-xs text-[#8c9284]">-</span>
                                                    ) : (
                                                        <ul className="list-disc space-y-0.5 pl-4 text-xs text-[#525a4a]">
                                                            {b.pesan.map((m, i) => <li key={i}>{m}</li>)}
                                                        </ul>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {hasil.terpotong && (
                                <p className="border-t border-[#e5e7dc] px-5 py-3 text-xs font-medium text-[#6c7465]">
                                    Hanya 300 baris pertama yang ditampilkan. Seluruh baris tetap diproses saat disimpan.
                                </p>
                            )}

                            <div className="flex flex-wrap items-center gap-3 border-t border-[#e5e7dc] bg-[#f9faf6] px-5 py-4">
                                <button type="button" onClick={simpan} disabled={r.baru === 0 || menyimpan} className={btnPrimary}>
                                    <Icon name="check" size={16} />
                                    {menyimpan ? 'Menyimpan...' : `Simpan ${r.baru} Baris`}
                                </button>
                                <Link href={`/impor?jenis=${jenis}`} className={btnSecondary}>Batalkan dan Unggah Ulang</Link>
                                {(r.galat > 0 || r.duplikat > 0) && (
                                    <p className="text-xs font-medium text-[#8a6d1f]">
                                        {r.galat + r.duplikat} baris tidak akan disimpan. Perbaiki di berkas lalu unggah ulang bila perlu.
                                    </p>
                                )}
                            </div>
                        </Card>
                    )}
                </div>

                <Card title="Nilai yang diterima" description="Pakai kode atau tulisan lengkap, huruf besar/kecil tidak berpengaruh.">
                    <div className="space-y-4 text-sm text-[#38433a]">
                        <div>
                            <p className="text-xs font-bold text-[#525a4a]">Tanggal</p>
                            <p>2020-01-31 atau 31/01/2020</p>
                        </div>

                        {jenis === 'lks' ? (
                            <>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">kategori</p>
                                    <p>{Object.values(opsi.kategoriLks).join(', ')}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">kelurahan / kecamatan</p>
                                    <p>
                                        {isOperator
                                            ? 'Otomatis wilayah akun Anda. Kolom boleh kosong; bila diisi harus sama.'
                                            : 'Harus sama dengan nama di menu Wilayah. Isi kecamatan bila nama kelurahan ada di lebih dari satu kecamatan.'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">status</p>
                                    <p>aktif atau nonaktif (kosong = aktif)</p>
                                </div>
                            </>
                        ) : (
                            <>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">nama_lks</p>
                                    <p>Harus sama persis dengan nama LKS yang sudah terdaftar. Kategori klien mengikuti kategori LKS itu.</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">jenis_kelamin</p>
                                    <p>L atau P (atau Laki-laki / Perempuan)</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">latar_belakang</p>
                                    <ul className="mt-1 space-y-0.5">
                                        {Object.entries(opsi.latarBelakang).map(([k, list]) => (
                                            <li key={k}><span className="font-bold">{opsi.kategoriLks[k]}:</span> {list.join(', ')}</li>
                                        ))}
                                    </ul>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">pendidikan (khusus Anak)</p>
                                    <p>{opsi.pendidikan.join(', ')}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">ragam_disabilitas (wajib untuk Disabilitas)</p>
                                    <p>{opsi.ragam.join('; ')}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">tingkat_ketergantungan (wajib untuk Lanjut Usia dan Disabilitas)</p>
                                    <p>{opsi.tingkat.join('; ')}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold text-[#525a4a]">nik</p>
                                    <p>16 digit angka atau kosong. NIK yang sudah terdaftar dilewati sebagai duplikat.</p>
                                </div>
                            </>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
