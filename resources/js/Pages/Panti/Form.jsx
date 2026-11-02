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

    return (
        <AppLayout>
            <Head title={editing ? `Edit ${panti.nama} - DISKATmsa` : 'Tambah Panti Asuhan Baru - DISKATmsa'} />

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

            <form onSubmit={submit} className="max-w-4xl space-y-7">
                <Card
                    title="Profil Identitas Kelembagaan"
                    description="Informasi utama identitas panti asuhan yang terdaftar di Dinas Sosial"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Nama Panti Asuhan"
                            required
                            error={errors.nama}
                            hint="Contoh: Panti Asuhan Kasih Minahasa"
                            className="sm:col-span-2"
                        >
                            <input
                                value={data.nama}
                                onChange={(e) => setData('nama', e.target.value)}
                                placeholder="Ketik nama lengkap lembaga panti..."
                                className={inputClass}
                                autoFocus
                            />
                        </Field>

                        <Field
                            label="Alamat Lengkap Panti"
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
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Wilayah Kelurahan / Desa"
                            required={!isOperator}
                            error={errors.kelurahan_id}
                            hint={isOperator ? 'Otomatis terkunci sesuai wilayah penugasan akun Anda.' : 'Pilih kelurahan atau desa di Kabupaten Minahasa'}
                        >
                            <select
                                value={data.kelurahan_id}
                                onChange={(e) => setData('kelurahan_id', e.target.value)}
                                disabled={isOperator}
                                className={inputClass}
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
                            label="Status Operasional Panti"
                            required
                            error={errors.status}
                            hint="Pilih 'Aktif' jika lembaga sedang aktif beroperasi membina anak"
                        >
                            <select
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className={inputClass}
                            >
                                <option value="aktif">Aktif Beroperasi</option>
                                <option value="nonaktif">Nonaktif / Tidak Beroperasi</option>
                            </select>
                        </Field>

                        <Field
                            label="Nama Pimpinan / Pengelola"
                            error={errors.nama_pengelola}
                            hint="Nama kepala panti atau penanggung jawab harian"
                        >
                            <input
                                value={data.nama_pengelola}
                                onChange={(e) => setData('nama_pengelola', e.target.value)}
                                placeholder="Contoh: Pdt. Yohanes Rondonuwu, M.Th"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Kapasitas Daya Tampung (Anak)"
                            error={errors.kapasitas}
                            hint="Maksimal anak asuh yang dapat ditampung di asrama"
                        >
                            <input
                                type="number"
                                min="1"
                                max="1000"
                                value={data.kapasitas}
                                onChange={(e) => setData('kapasitas', e.target.value)}
                                placeholder="Contoh: 50"
                                className={inputClass}
                            />
                        </Field>
                    </div>
                </Card>

                <Card
                    title="Legalitas Perizinan & Kontak Resmi"
                    description="Pencatatan nomor izin resmi dan saluran komunikasi lembaga"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Nomor SK Pendirian / Akta Notaris"
                            error={errors.no_sk}
                            hint="Nomor surat keputusan atau nomor akta pendirian"
                        >
                            <input
                                value={data.no_sk}
                                onChange={(e) => setData('no_sk', e.target.value)}
                                placeholder="Contoh: 460/SK-PNT/2020"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Tanggal Berdiri Lembaga"
                            error={errors.tanggal_berdiri}
                            hint="Tanggal resmi berdirinya panti sesuai akta pendirian"
                        >
                            <input
                                type="date"
                                value={data.tanggal_berdiri}
                                onChange={(e) => setData('tanggal_berdiri', e.target.value)}
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Nomor Telepon / WhatsApp Kantor"
                            error={errors.telepon}
                            hint="Nomor yang aktif dan dapat dihubungi oleh petugas"
                        >
                            <input
                                value={data.telepon}
                                onChange={(e) => setData('telepon', e.target.value)}
                                placeholder="Contoh: 081234567890 atau (0431) 321xxx"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Alamat Surat Elektronik (Email)"
                            error={errors.email}
                            hint="Email resmi lembaga panti asuhan"
                        >
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="Contoh: sekretariat@pantikasih.org"
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
                        {processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan' : 'Daftarkan Panti Baru'}
                    </button>
                    <Link
                        href={editing ? `/panti/${panti.id}` : '/panti'}
                        className={btnSecondary}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
