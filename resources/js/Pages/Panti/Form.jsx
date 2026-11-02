import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import { Card, Field, PageHeader, btnPrimary, btnSecondary, inputClass } from '../../Components/ui';

export default function Form({ panti, kelurahans }) {
    const isOperator = usePage().props.auth.user.role === 'operator';
    const editing = Boolean(panti);

    const { data, setData, post, put, processing, errors } = useForm({
        nama: panti?.nama ?? '',
        alamat: panti?.alamat ?? '',
        kelurahan_id: panti?.kelurahan_id ?? (isOperator ? (kelurahans[0]?.id ?? '') : ''),
        no_sk: panti?.no_sk ?? '',
        tanggal_berdiri: panti?.tanggal_berdiri ?? '',
        telepon: panti?.telepon ?? '',
        email: panti?.email ?? '',
        nama_pengelola: panti?.nama_pengelola ?? '',
        kapasitas: panti?.kapasitas ?? '',
        status: panti?.status ?? 'aktif',
    });

    function submit(event) {
        event.preventDefault();
        editing ? put(`/panti/${panti.id}`) : post('/panti');
    }

    // Ekstraksi class tambahan untuk input agar selaras dengan tema (hijau zaitun/emas)
    const customInputClass = `${inputClass} bg-[#f7f8f3]/50 border-[#e2e4d8] text-[#172b25] transition-all duration-200 focus:bg-white focus:border-[#b08935] focus:ring-1 focus:ring-[#b08935] placeholder:text-[#8a9182] rounded-xl`;

    return (
        <AppLayout>
            <Head title={editing ? `Edit ${panti.nama} - SADAP` : 'Tambah Panti Asuhan Baru - SADAP'} />

            <PageHeader
                title={editing ? `Edit Data: ${panti.nama}` : 'Tambah Lembaga Panti Asuhan Baru'}
                description={
                    editing
                        ? 'Perbarui informasi dasar identitas, kontak, dan kapasitas panti asuhan.'
                        : 'Lengkapi profil awal panti asuhan. Data anak asuh, kepengurusan, dan dokumen legalitas dapat ditambahkan setelah panti tersimpan.'
                }
                breadcrumbs={[
                    { label: 'Panti Asuhan', href: '/panti' },
                    { label: editing ? 'Edit Data Panti' : 'Pendaftaran Panti Baru' },
                ]}
            />

            <form onSubmit={submit} className="max-w-4xl space-y-8 pb-10">
                <Card
                    title={<span className="text-[#172b25] font-extrabold text-lg">Profil Identitas Kelembagaan</span>}
                    description={<span className="text-[#5a6253]">Informasi utama identitas panti asuhan yang terdaftar di Dinas Sosial</span>}
                    className="border-[#e2e4d8] shadow-sm rounded-2xl"
                >
                    <div className="grid gap-6 sm:grid-cols-2 pt-2">
                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Nama Panti Asuhan</span>}
                            required
                            error={errors.nama}
                            hint="Contoh: Panti Asuhan Kasih Minahasa"
                            className="sm:col-span-2"
                        >
                            <input
                                value={data.nama}
                                onChange={(e) => setData('nama', e.target.value)}
                                placeholder="Ketik nama lengkap lembaga panti..."
                                className={customInputClass}
                                autoFocus
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Alamat Lengkap Panti</span>}
                            required
                            error={errors.alamat}
                            hint="Tuliskan nama jalan, nomor, RT/RW, dan patokan lokasi yang jelas"
                            className="sm:col-span-2"
                        >
                            <textarea
                                rows={3}
                                value={data.alamat}
                                onChange={(e) => setData('alamat', e.target.value)}
                                placeholder="Contoh: Jl. Kasih No. 12, Lingkungan II..."
                                className={`${customInputClass} resize-none`}
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Wilayah Kelurahan / Desa</span>}
                            required={!isOperator}
                            error={errors.kelurahan_id}
                            hint={isOperator ? 'Otomatis terkunci sesuai wilayah penugasan akun Anda.' : 'Pilih kelurahan atau desa di Kabupaten Minahasa'}
                        >
                            <select
                                value={data.kelurahan_id}
                                onChange={(e) => setData('kelurahan_id', e.target.value)}
                                disabled={isOperator}
                                className={customInputClass}
                            >
                                <option value="">-- Pilih Wilayah Kelurahan / Desa --</option>
                                {kelurahans.map((k) => (
                                    <option key={k.id} value={k.id}>
                                        {k.nama} — Kec. {k.kecamatan}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Status Operasional Panti</span>}
                            required
                            error={errors.status}
                            hint="Pilih 'Aktif' jika lembaga sedang aktif beroperasi membina anak"
                        >
                            <select
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className={customInputClass}
                            >
                                <option value="aktif">Aktif Beroperasi</option>
                                <option value="nonaktif">Nonaktif / Tidak Beroperasi</option>
                            </select>
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Nama Pimpinan / Pengelola</span>}
                            error={errors.nama_pengelola}
                            hint="Nama kepala panti atau penanggung jawab harian"
                        >
                            <input
                                value={data.nama_pengelola}
                                onChange={(e) => setData('nama_pengelola', e.target.value)}
                                placeholder="Contoh: Pdt. Yohanes Rondonuwu, M.Th"
                                className={customInputClass}
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Kapasitas Daya Tampung</span>}
                            error={errors.kapasitas}
                            hint="Maksimal anak asuh yang dapat ditampung di asrama"
                        >
                            <div className="relative">
                                <input
                                    type="number"
                                    min="1"
                                    max="1000"
                                    value={data.kapasitas}
                                    onChange={(e) => setData('kapasitas', e.target.value)}
                                    placeholder="Contoh: 50"
                                    className={`${customInputClass} pr-14`}
                                />
                                <span className="absolute inset-y-0 right-4 flex items-center text-xs font-bold text-[#8a9182] pointer-events-none">
                                    Anak
                                </span>
                            </div>
                        </Field>
                    </div>
                </Card>

                <Card
                    title={<span className="text-[#172b25] font-extrabold text-lg">Legalitas Perizinan & Kontak Resmi</span>}
                    description={<span className="text-[#5a6253]">Pencatatan nomor izin resmi dan saluran komunikasi lembaga</span>}
                    className="border-[#e2e4d8] shadow-sm rounded-2xl"
                >
                    <div className="grid gap-6 sm:grid-cols-2 pt-2">
                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Nomor SK Pendirian / Akta</span>}
                            error={errors.no_sk}
                            hint="Nomor surat keputusan atau nomor akta pendirian"
                        >
                            <input
                                value={data.no_sk}
                                onChange={(e) => setData('no_sk', e.target.value)}
                                placeholder="Contoh: 460/SK-PNT/2020"
                                className={customInputClass}
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Tanggal Berdiri Lembaga</span>}
                            error={errors.tanggal_berdiri}
                            hint="Tanggal resmi berdirinya panti sesuai akta pendirian"
                        >
                            <input
                                type="date"
                                value={data.tanggal_berdiri}
                                onChange={(e) => setData('tanggal_berdiri', e.target.value)}
                                className={customInputClass}
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Nomor Telepon / WhatsApp</span>}
                            error={errors.telepon}
                            hint="Nomor yang aktif dan dapat dihubungi oleh petugas"
                        >
                            <input
                                value={data.telepon}
                                onChange={(e) => setData('telepon', e.target.value)}
                                placeholder="Contoh: 081234567890 atau (0431) 321xxx"
                                className={customInputClass}
                            />
                        </Field>

                        <Field
                            label={<span className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Alamat Surel (Email)</span>}
                            error={errors.email}
                            hint="Email resmi lembaga panti asuhan"
                        >
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="Contoh: sekretariat@pantikasih.org"
                                className={customInputClass}
                            />
                        </Field>
                    </div>
                </Card>

                {/* Bagian Tombol Aksi */}
                <div className="flex flex-wrap items-center gap-4 pt-4 border-t border-[#e2e4d8]">
                    <button
                        type="submit"
                        disabled={processing}
                        className={`${btnPrimary} min-w-[200px] py-3 rounded-xl bg-[#172b25] text-white hover:bg-[#203b2e] hover:shadow-md transition-all font-bold flex items-center justify-center gap-2`}
                    >
                        <Icon name={processing ? 'loader' : 'check'} size={18} className={processing ? 'animate-spin' : ''} />
                        <span>{processing ? 'Menyimpan Data...' : editing ? 'Simpan Perubahan' : 'Daftarkan Panti Baru'}</span>
                    </button>
                    <Link
                        href={editing ? `/panti/${panti.id}` : '/panti'}
                        className={`${btnSecondary} py-3 px-6 rounded-xl border border-[#e2e4d8] bg-white text-[#5a6253] font-bold hover:bg-[#f7f8f3] hover:text-[#172b25] transition-colors`}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}