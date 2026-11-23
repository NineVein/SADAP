import { useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import KategoriBadge from '../../Components/KategoriBadge';
import { Card, Field, PageHeader, btnPrimary, btnSecondary, inputClass } from '../../Components/ui';

const KATEGORI_LABEL = { anak: 'LKS Anak', lanjut_usia: 'LKS Lanjut Usia', disabilitas: 'LKS Disabilitas' };

/** Istilah isian menyesuaikan kategori klien agar formulir terasa tepat untuk tiap layanan. */
const ISTILAH = {
    anak: { wali: 'Nama Orang Tua / Wali / Kerabat', waliHint: 'Nama penanggung jawab keluarga jika masih ada', kondisi: 'Latar Belakang Sosial & Pendidikan' },
    lanjut_usia: { wali: 'Nama Keluarga / Penanggung Jawab', waliHint: 'Anak, kerabat, atau pihak yang dapat dihubungi (kosongkan bila tidak ada)', kondisi: 'Latar Belakang Sosial & Kondisi Perawatan' },
    disabilitas: { wali: 'Nama Keluarga / Pendamping', waliHint: 'Keluarga atau pendamping yang bertanggung jawab (kosongkan bila tidak ada)', kondisi: 'Ragam Disabilitas & Kondisi Perawatan' },
};

export default function Form({ anak, defaultPantiId, defaultJenis, pantiOptions, options }) {
    const editing = Boolean(anak);
    const dari = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('dari') ?? '' : '';

    // Saat tambah baru dengan kategori terpilih, hanya tampilkan LKS kategori itu.
    const daftarLks = !editing && defaultJenis ? pantiOptions.filter((p) => p.kategori_lks === defaultJenis) : pantiOptions;

    const { data, setData, post, put, processing, errors } = useForm({
        dari,
        panti_id: anak?.panti_id ?? defaultPantiId ?? (daftarLks.length === 1 ? daftarLks[0].id : ''),
        nama: anak?.nama ?? '',
        nik: anak?.nik ?? '',
        jenis_kelamin: anak?.jenis_kelamin ?? '',
        tempat_lahir: anak?.tempat_lahir ?? '',
        tanggal_lahir: anak?.tanggal_lahir ?? '',
        kategori: anak?.kategori ?? '',
        jenis_disabilitas: anak?.jenis_disabilitas ?? '',
        tingkat_ketergantungan: anak?.tingkat_ketergantungan ?? '',
        pendidikan: anak?.pendidikan ?? '',
        nama_wali: anak?.nama_wali ?? '',
        tanggal_masuk: anak?.tanggal_masuk ?? '',
        status: anak?.status ?? 'aktif',
        tanggal_keluar: anak?.tanggal_keluar ?? '',
        keterangan: anak?.keterangan ?? '',
    });

    // Kategori klien selalu mengikuti kategori LKS yang dipilih (ditentukan server saat disimpan).
    const lks = pantiOptions.find((p) => String(p.id) === String(data.panti_id));
    const kategoriKlien = lks?.kategori_lks ?? anak?.kategori_klien ?? null;
    const latar = (kategoriKlien && options.latarBelakang?.[kategoriKlien]) || {};
    const istilah = ISTILAH[kategoriKlien] ?? ISTILAH.anak;
    const perluKetergantungan = kategoriKlien === 'lanjut_usia' || kategoriKlien === 'disabilitas';

    // Bila LKS diganti ke kategori lain, pilihan yang tidak berlaku dikosongkan.
    useEffect(() => {
        if (kategoriKlien && data.kategori && !latar[data.kategori]) setData('kategori', '');
    }, [kategoriKlien]);

    function submit(event) {
        event.preventDefault();
        editing ? put(`/klien/${anak.id}`) : post('/klien');
    }

    const backUrl =
        dari === 'panti' && data.panti_id
            ? `/lks/${data.panti_id}?tab=anak`
            : data.panti_id
            ? `/klien?panti_id=${data.panti_id}`
            : '/klien';

    const hintUsia =
        kategoriKlien === 'anak'
            ? `Klien kategori Anak berusia maksimal ${options.usia?.anak_maks ?? 18} tahun`
            : kategoriKlien === 'lanjut_usia'
            ? `Klien kategori Lanjut Usia berusia minimal ${options.usia?.lansia_min ?? 60} tahun`
            : 'Tanggal, bulan, dan tahun lahir';

    // Kelompokkan pilihan LKS menurut kategori agar tiga jenis layanan terpisah jelas.
    const kelompok = Object.keys(KATEGORI_LABEL)
        .map((k) => [k, daftarLks.filter((p) => p.kategori_lks === k)])
        .filter(([, list]) => list.length > 0);

    return (
        <AppLayout>
            <Head title={editing ? `Edit ${anak.nama} - DISKATmsa` : 'Tambah Klien Baru - DISKATmsa'} />

            <PageHeader
                title={editing ? `Edit Data: ${anak.nama}` : 'Pendaftaran Klien Baru'}
                description={
                    editing
                        ? 'Perbarui biodata, kondisi, status keberadaan di LKS, dan data kependudukan klien.'
                        : 'Pilih LKS terlebih dahulu. Kategori klien (anak, lanjut usia, atau disabilitas) mengikuti kategori LKS yang dipilih.'
                }
                breadcrumbs={[
                    { label: 'Klien', href: '/klien' },
                    { label: editing ? 'Edit Data Klien' : 'Tambah Klien Baru' },
                ]}
            />

            <form onSubmit={submit} className="max-w-4xl space-y-7">
                <Card
                    title="LKS Tempat Klien Tinggal"
                    description="Satu klien tinggal di satu LKS. Kategori klien mengikuti kategori LKS."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Lembaga Kesejahteraan Sosial (LKS)"
                            required
                            error={errors.panti_id}
                            className="sm:col-span-2"
                        >
                            <select
                                value={data.panti_id}
                                onChange={(e) => setData('panti_id', e.target.value)}
                                className={inputClass}
                            >
                                <option value="">-- Pilih LKS --</option>
                                {kelompok.map(([k, list]) => (
                                    <optgroup key={k} label={KATEGORI_LABEL[k]}>
                                        {list.map((p) => (
                                            <option key={p.id} value={p.id}>{p.nama}</option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </Field>

                        <div className="sm:col-span-2 flex flex-wrap items-center gap-2 rounded-xl border border-[#e2e4d8] bg-[#f7f8f3] px-4 py-3 text-sm">
                            <span className="font-semibold text-[#3e473b]">Kategori klien:</span>
                            {kategoriKlien ? (
                                <KategoriBadge value={kategoriKlien} />
                            ) : (
                                <span className="text-[#8a9182]">akan ditentukan setelah LKS dipilih</span>
                            )}
                        </div>
                    </div>
                </Card>

                <Card
                    title="Identitas & Kependudukan Klien"
                    description="Informasi personal dan nomor induk kependudukan resmi"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Nama Lengkap Klien"
                            required
                            error={errors.nama}
                            hint="Nama lengkap sesuai KTP, KIA, akta kelahiran, atau kartu keluarga"
                            className="sm:col-span-2"
                        >
                            <input
                                value={data.nama}
                                onChange={(e) => setData('nama', e.target.value)}
                                placeholder="Contoh: Michael Timothy Wowor"
                                className={inputClass}
                                autoFocus
                            />
                        </Field>

                        <Field
                            label="Nomor Induk Kependudukan (NIK)"
                            error={errors.nik}
                            hint="16 digit angka (kosongkan bila belum ada)"
                        >
                            <input
                                inputMode="numeric"
                                maxLength={16}
                                value={data.nik}
                                onChange={(e) => setData('nik', e.target.value.replace(/\D/g, ''))}
                                placeholder="Contoh: 710201..."
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Jenis Kelamin" required error={errors.jenis_kelamin}>
                            <select
                                value={data.jenis_kelamin}
                                onChange={(e) => setData('jenis_kelamin', e.target.value)}
                                className={inputClass}
                            >
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </Field>

                        <Field label="Tempat Lahir" error={errors.tempat_lahir} hint="Kabupaten/kota kelahiran">
                            <input
                                value={data.tempat_lahir}
                                onChange={(e) => setData('tempat_lahir', e.target.value)}
                                placeholder="Contoh: Tondano"
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Tanggal Lahir" required error={errors.tanggal_lahir} hint={hintUsia}>
                            <input
                                type="date"
                                value={data.tanggal_lahir}
                                onChange={(e) => setData('tanggal_lahir', e.target.value)}
                                className={inputClass}
                            />
                        </Field>
                    </div>
                </Card>

                <Card title={istilah.kondisi} description="Bagian ini menyesuaikan kategori klien">
                    {!kategoriKlien ? (
                        <p className="text-sm font-medium text-[#8a9182]">Pilih LKS terlebih dahulu untuk menampilkan isian yang sesuai.</p>
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label="Latar Belakang Sosial"
                                required
                                error={errors.kategori}
                                hint="Kondisi yang melatarbelakangi klien berada di LKS"
                            >
                                <select
                                    value={data.kategori}
                                    onChange={(e) => setData('kategori', e.target.value)}
                                    className={inputClass}
                                >
                                    <option value="">-- Pilih Latar Belakang --</option>
                                    {Object.entries(latar).map(([v, l]) => (
                                        <option key={v} value={v}>{l}</option>
                                    ))}
                                </select>
                            </Field>

                            {kategoriKlien === 'anak' && (
                                <Field
                                    label="Jenjang Pendidikan Terakhir / Aktif"
                                    error={errors.pendidikan}
                                    hint="Jenjang sekolah yang sedang ditempuh"
                                >
                                    <select
                                        value={data.pendidikan}
                                        onChange={(e) => setData('pendidikan', e.target.value)}
                                        className={inputClass}
                                    >
                                        <option value="">-- Belum Ditentukan / Tidak Sekolah --</option>
                                        {Object.entries(options.pendidikan).map(([v, l]) => (
                                            <option key={v} value={v}>{l}</option>
                                        ))}
                                    </select>
                                </Field>
                            )}

                            {kategoriKlien === 'disabilitas' && (
                                <Field
                                    label="Ragam Disabilitas"
                                    required
                                    error={errors.jenis_disabilitas}
                                    hint="Pilih ragam yang paling sesuai dengan kondisi klien"
                                >
                                    <select
                                        value={data.jenis_disabilitas}
                                        onChange={(e) => setData('jenis_disabilitas', e.target.value)}
                                        className={inputClass}
                                    >
                                        <option value="">-- Pilih Ragam Disabilitas --</option>
                                        {Object.entries(options.jenisDisabilitas).map(([v, l]) => (
                                            <option key={v} value={v}>{l}</option>
                                        ))}
                                    </select>
                                </Field>
                            )}

                            {perluKetergantungan && (
                                <Field
                                    label="Tingkat Ketergantungan Perawatan"
                                    required
                                    error={errors.tingkat_ketergantungan}
                                    hint="Seberapa besar bantuan yang dibutuhkan untuk aktivitas sehari-hari"
                                >
                                    <select
                                        value={data.tingkat_ketergantungan}
                                        onChange={(e) => setData('tingkat_ketergantungan', e.target.value)}
                                        className={inputClass}
                                    >
                                        <option value="">-- Pilih Tingkat --</option>
                                        {Object.entries(options.tingkat).map(([v, l]) => (
                                            <option key={v} value={v}>{l}</option>
                                        ))}
                                    </select>
                                </Field>
                            )}

                            <Field
                                label={istilah.wali}
                                error={errors.nama_wali}
                                hint={istilah.waliHint}
                                className="sm:col-span-2"
                            >
                                <input
                                    value={data.nama_wali}
                                    onChange={(e) => setData('nama_wali', e.target.value)}
                                    placeholder="Contoh: Yohana Wowor (Bibi)"
                                    className={inputClass}
                                />
                            </Field>
                        </div>
                    )}
                </Card>

                <Card title="Status Keberadaan di LKS" description="Catatan masa pelayanan klien di dalam LKS">
                    <div className="grid gap-5 sm:grid-cols-3">
                        <Field label="Tanggal Masuk LKS" error={errors.tanggal_masuk} hint="Awal klien mulai tinggal di LKS">
                            <input
                                type="date"
                                value={data.tanggal_masuk}
                                onChange={(e) => setData('tanggal_masuk', e.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Status Keaktifan"
                            required
                            error={errors.status}
                            hint="Pilih 'Aktif' jika klien saat ini tinggal di LKS"
                        >
                            <select
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className={inputClass}
                            >
                                <option value="aktif">Aktif Tinggal di LKS</option>
                                <option value="keluar">Sudah Keluar</option>
                            </select>
                        </Field>

                        {data.status === 'keluar' && (
                            <Field label="Tanggal Keluar" error={errors.tanggal_keluar} hint="Tanggal selesai pelayanan">
                                <input
                                    type="date"
                                    value={data.tanggal_keluar}
                                    onChange={(e) => setData('tanggal_keluar', e.target.value)}
                                    className={inputClass}
                                />
                            </Field>
                        )}

                        <Field
                            label="Catatan Khusus / Keterangan"
                            error={errors.keterangan}
                            hint="Kondisi kesehatan, kebutuhan khusus, atau riwayat pelayanan"
                            className="sm:col-span-3"
                        >
                            <textarea
                                rows={2}
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Catatan tambahan mengenai kondisi klien..."
                                className={inputClass}
                            />
                        </Field>
                    </div>
                </Card>

                <div className="flex flex-wrap items-center gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className={`${btnPrimary} min-w-[180px] text-base py-3`}
                    >
                        <Icon name="check" size={18} />
                        {processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan Klien' : 'Daftarkan Klien'}
                    </button>
                    <Link href={backUrl} className={btnSecondary}>
                        Batal
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
