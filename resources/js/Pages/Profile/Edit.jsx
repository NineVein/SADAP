import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import { Card, Field, PageHeader, btnPrimary, inputClass } from '../../Components/ui';

export default function Edit() {
    const user = usePage().props.auth.user;

    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        put('/profil/password', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <AppLayout>
            <Head title="Profil & Keamanan Akun - DISKATmsa" />

            <PageHeader
                title="Profil Pengguna & Keamanan Kata Sandi"
                description={`Akun terdaftar: ${user.name} (${user.email}) · Peran: ${user.role_label || user.role}`}
                breadcrumbs={[
                    { label: 'Profil Akun' },
                ]}
            />

            <div className="max-w-xl">
                <Card
                    title="Perbarui Kata Sandi (Password)"
                    description="Gunakan kata sandi yang kuat dengan minimal 8 karakter yang memadukan huruf dan angka."
                >
                    <form onSubmit={submit} className="space-y-5">
                        <Field
                            label="Kata Sandi Saat Ini"
                            required
                            error={errors.current_password}
                            hint="Masukkan kata sandi lama Anda untuk konfirmasi keamanan"
                        >
                            <input
                                type="password"
                                autoComplete="current-password"
                                value={data.current_password}
                                onChange={(e) => setData('current_password', e.target.value)}
                                placeholder="Masukkan password saat ini..."
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Kata Sandi Baru"
                            required
                            error={errors.password}
                            hint="Minimal 8 karakter"
                        >
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Masukkan password baru..."
                                className={inputClass}
                            />
                        </Field>

                        <Field
                            label="Konfirmasi Kata Sandi Baru"
                            required
                            hint="Ketik ulang kata sandi baru untuk memastikan tidak ada salah ketik"
                        >
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Ulangi password baru..."
                                className={inputClass}
                            />
                        </Field>

                        <div className="pt-2">
                            <button type="submit" disabled={processing} className={btnPrimary}>
                                <Icon name="check" size={16} />
                                {processing ? 'Menyimpan...' : 'Perbarui Kata Sandi'}
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
