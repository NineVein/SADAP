import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminTabs from '../../Components/AdminTabs';
import Icon from '../../Components/Icon';
import { Card, Field, PageHeader, btnPrimary, btnSecondary, inputClass } from '../../Components/ui';

export default function Form({ user, roles, kelurahans, isSelf = false }) {
    const editing = Boolean(user);

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        role_id: user?.role_id ?? '',
        kelurahan_id: user?.kelurahan_id ?? '',
        password: '',
        password_confirmation: '',
        is_active: user?.is_active ?? true,
    });

    const selectedRole = roles.find((r) => String(r.id) === String(data.role_id));
    const needsKelurahan = selectedRole?.name === 'operator';

    function submit(event) {
        event.preventDefault();
        editing ? put(`/users/${user.id}`) : post('/users');
    }

    return (
        <AppLayout>
            <AdminTabs />
            <Head title={editing ? `Edit Pengguna: ${user.name} - DISKATmsa` : 'Tambah Pengguna Baru - DISKATmsa'} />

            <PageHeader
                title={editing ? `Edit Pengguna: ${user.name}` : 'Tambah Pengguna Baru Sistem'}
                description={
                    editing
                        ? `Memperbarui akun dan hak akses: ${user.email}`
                        : 'Daftarkan petugas Dinas Sosial atau Operator Kelurahan/Desa untuk mengakses sistem.'
                }
                breadcrumbs={[
                    { label: 'Pengaturan Sistem', href: '/users' },
                    { label: 'Pengguna', href: '/users' },
                    { label: editing ? 'Edit Pengguna' : 'Tambah Baru' },
                ]}
            />

            <form onSubmit={submit} className="max-w-3xl space-y-7">
                <Card
                    title="Informasi Kredensial Akun"
                    description="Identitas nama dan alamat email login"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Nama Lengkap Petugas"
                            required
                            error={errors.name}
                            hint="Nama lengkap beserta gelar jika ada"
                        >
                            <input
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Contoh: Johannes Palit, S.Sos"
                                className={inputClass}
                                autoFocus
                            />
                        </Field>

                        <Field
                            label="Alamat Email Resmi"
                            required
                            error={errors.email}
                            hint="Digunakan untuk masuk (login) ke dalam sistem"
                        >
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="nama@dinsos.minahasa.go.id"
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Kata Sandi (Password)"
                            required={!editing}
                            error={errors.password}
                            hint={editing ? 'Kosongkan jika kata sandi tidak ingin diubah.' : 'Minimal 8 karakter, perpaduan huruf dan angka.'}
                        >
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Masukkan password..."
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Ulangi Kata Sandi"
                            required={!editing}
                            hint="Konfirmasi ulang password"
                        >
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Ulangi password..."
                                className={inputClass}
                            />
                        </Field>
                    </div>
                </Card>

                <Card
                    title="Penetapan Peran & Wilayah Kerja"
                    description={isSelf ? 'Peran dan status keaktifan akun Anda sendiri terkunci demi keamanan sistem.' : 'Tentukan tingkat kewenangan dan wilayah kerja petugas'}
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            label="Peran Hak Akses"
                            required
                            error={errors.role_id}
                            hint="Admin (semua akses), Supervisor (review/validasi), Operator (wilayah tertentu)"
                        >
                            <select
                                value={data.role_id}
                                onChange={(e) => setData('role_id', e.target.value)}
                                disabled={isSelf}
                                className={inputClass}
                            >
                                <option value="">-- Pilih Peran Hak Akses --</option>
                                {roles.map((r) => (
                                    <option key={r.id} value={r.id}>{r.label}</option>
                                ))}
                            </select>
                        </Field>

                        {needsKelurahan && (
                            <Field
                                label="Wilayah Kelurahan / Desa"
                                required
                                error={errors.kelurahan_id}
                                hint="Operator hanya berwenang mengelola panti di wilayah ini"
                            >
                                <select
                                    value={data.kelurahan_id}
                                    onChange={(e) => setData('kelurahan_id', e.target.value)}
                                    disabled={isSelf}
                                    className={inputClass}
                                >
                                    <option value="">-- Pilih Wilayah Kelurahan/Desa --</option>
                                    {kelurahans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama} — Kec. {k.kecamatan}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        )}

                        <div className="sm:col-span-2 pt-2">
                            <label className="flex items-center gap-3 text-sm font-semibold text-[#203126] cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    disabled={isSelf}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-5 w-5 rounded border-[#d3d6c8] text-[#203b2e] focus:ring-[#203b2e]"
                                />
                                <span>Akun Aktif (Dapat Masuk dan Menggunakan Sistem)</span>
                            </label>
                        </div>
                    </div>
                </Card>

                <div className="flex flex-wrap items-center gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className={`${btnPrimary} min-w-[180px] text-base py-3`}
                    >
                        <Icon name="check" size={18} />
                        {processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan Pengguna' : 'Daftarkan Pengguna Baru'}
                    </button>
                    <Link href="/users" className={btnSecondary}>
                        Batal
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
