import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import { Card, Field, PageHeader, btnPrimary, btnSecondary, inputClass } from '../../Components/ui';

export default function Form({ anak, defaultPantiId, pantiOptions, options }) {
    const editing = Boolean(anak);
    const dari = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('dari') ?? '' : '';

    const { data, setData, post, put, processing, errors } = useForm({
        dari,
        panti_id: anak?.panti_id ?? defaultPantiId ?? (pantiOptions.length === 1 ? pantiOptions[0].id : ''),
        nama: anak?.nama ?? '',
        nik: anak?.nik ?? '',
        jenis_kelamin: anak?.jenis_kelamin ?? '',
        tempat_lahir: anak?.tempat_lahir ?? '',
        tanggal_lahir: anak?.tanggal_lahir ?? '',
        kategori: anak?.kategori ?? 'dhuafa',
        pendidikan: anak?.pendidikan ?? '',
        nama_wali: anak?.nama_wali ?? '',
        tanggal_masuk: anak?.tanggal_masuk ?? '',
        status: anak?.status ?? 'aktif',
        tanggal_keluar: anak?.tanggal_keluar ?? '',
        keterangan: anak?.keterangan ?? '',
    });

    function submit(event) {
        event.preventDefault();
        editing ? put(`/anak/${anak.id}`) : post('/anak');
    }

    const backUrl =
        dari === 'panti' && data.panti_id
            ? `/panti/${data.panti_id}?tab=anak`
            : data.panti_id
            ? `/anak?panti_id=${data.panti_id}`
            : '/anak';

    return (
        <AppLayout>
            <Head title={editing ? `Edit ${anak.nama} - DISKATmsa` : 'Tambah Anak Binaan Baru - DISKATmsa'} />

            <PageHeader
                title={editing ? `Edit Data: ${anak.nama}` : 'Pendaftaran Anak Binaan Baru'}
                description={
                    editing
                        ? 'Perbarui informasi biodata, status asuhan, dan data kependudukan anak asuh.'
                        : 'Lengkapi formulir pencatatan anak asuh baru untuk verifikasi desil dan bantuan sosial Dinas Sosial.'
                }
                breadcrumbs={[
                    { label: 'Anak & Desil', href: '/anak' },
                    { label: editing ? 'Edit Data Anak' : 'Tambah Anak Baru' },
                ]}
            />

            <form onSubmit={submit} className="max-w-4xl space-y-7">
                <Card
                    title="Identitas & Kependudukan Anak"
                    description="Informasi personal dan nomor induk kependudukan resmi"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Panti Asuhan Pembina"
                            required
                            error={errors.panti_id}
                            hint="Pilih lembaga panti tempat anak tinggal/dibina"
                            className="sm:col-span-2"
                        >
                            <select
                                value={data.panti_id}
                                onChange={(e) => setData('panti_id', e.target.value)}
                                className={inputClass}
                            >
                                <option value="">-- Pilih Lembaga Panti Asuhan --</option>
                                {pantiOptions.map((p) => (
                                    <option key={p.id} value={p.id}>{p.nama}</option>
                                ))}
                            </select>
                        </Field>

                        <Field
                            label="Nama Lengkap Anak"
                            required
                            error={errors.nama}
                            hint="Nama lengkap sesuai akta kelahiran atau kartu keluarga"
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
                            hint="16 digit angka sesuai KIA atau Kartu Keluarga (kosongkan bila belum ada)"
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

                        <Field
                            label="Jenis Kelamin"
                            required
                            error={errors.jenis_kelamin}
                        >
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

                        <Field
                            label="Tempat Lahir"
                            error={errors.tempat_lahir}
                            hint="Kabupaten/kota kelahiran anak"
                        >
                            <input
                                value={data.tempat_lahir}
                                onChange={(e) => setData('tempat_lahir', e.target.value)}
                                placeholder="Contoh: Tondano"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Tanggal Lahir"
                            required
                            error={errors.tanggal_lahir}
                            hint="Tanggal, bulan, dan tahun lahir"
                        >
                            <input
                                type="date"
                                value={data.tanggal_lahir}
                                onChange={(e) => setData('tanggal_lahir', e.target.value)}
                                className={inputClass}
                            />
                        </Field>
                    </div>
                </Card>

                <Card
                    title="Latar Belakang Sosial & Pendidikan"
                    description="Kategori asuhan dan status pendidikan aktif"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Kategori Kebutuhan Asuhan"
                            required
                            error={errors.kategori}
                            hint="Status keluarga yang melatarbelakangi pengasuhan di panti"
                        >
                            <select
                                value={data.kategori}
                                onChange={(e) => setData('kategori', e.target.value)}
                                className={inputClass}
                            >
                                {Object.entries(options.kategori).map(([v, l]) => (
                                    <option key={v} value={v}>{l}</option>
                                ))}
                            </select>
                        </Field>

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

                        <Field
                            label="Nama Orang Tua / Wali / Kerabat"
                            error={errors.nama_wali}
                            hint="Nama penanggung jawab keluarga jika masih ada"
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
                </Card>

                <Card
                    title="Status Keberadaan di Asrama Panti"
                    description="Catatan masa pembinaan di panti asuhan"
                >
                    <div className="grid gap-5 sm:grid-cols-3">
                        <Field
                            label="Tanggal Masuk Panti"
                            error={errors.tanggal_masuk}
                            hint="Awal anak mulai tinggal di panti"
                        >
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
                            hint="Pilih 'Aktif' jika anak saat ini tinggal di asrama"
                        >
                            <select
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className={inputClass}
                            >
                                <option value="aktif">Aktif Tinggal di Panti</option>
                                <option value="keluar">Sudah Keluar / Mandiri</option>
                            </select>
                        </Field>

                        {data.status === 'keluar' && (
                            <Field
                                label="Tanggal Keluar"
                                error={errors.tanggal_keluar}
                                hint="Tanggal selesai pembinaan"
                            >
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
                            hint="Kondisi kesehatan, bakat minat, atau riwayat pembinaan"
                            className="sm:col-span-3"
                        >
                            <textarea
                                rows={2}
                                value={data.keterangan}
                                onChange={(e) => setData('keterangan', e.target.value)}
                                placeholder="Catatan tambahan mengenai kondisi anak..."
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
                        {processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan Anak' : 'Daftarkan Anak Asuh'}
                    </button>
                    <Link
                        href={backUrl}
                        className={btnSecondary}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
