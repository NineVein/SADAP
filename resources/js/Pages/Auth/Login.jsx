import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import Icon from '../../Components/Icon';
import { btnPrimary, inputClass } from '../../Components/ui';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);

    function submit(event) {
        event.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    }

    return (
        <>
            <Head title="Masuk ke Sistem - SADAP" />

            {/* Background utama menggunakan warna asli */}
            <main className="grid min-h-screen bg-[#f7f8f3] lg:grid-cols-[1fr_1.1fr]">
                
                {/* Panel Kiri Ilustratif */}
                <section className="relative hidden flex-col justify-between overflow-hidden bg-[#172b25] p-12 text-white lg:flex shadow-2xl">
                    <div aria-hidden="true" className="absolute -right-16 -top-20 h-[460px] w-[260px] rotate-[28deg] border-l border-[#d6b66d]/30" />
                    <div aria-hidden="true" className="absolute bottom-0 right-0 h-[45%] w-[70%] border-l border-t border-[#d6b66d]/25" />

                    <div className="relative flex items-center gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#203b2e] text-[#e0c27c] shadow-md border border-[#d6b66d]/30">
                            <Icon name="building" size={20} />
                        </div>
                        <div>
                            <p className="text-2xl font-black tracking-tight !text-[#fbf8f2]">
                                SADAP
                            </p>
                            <p className="text-xs font-semibold !text-[#d8dccf]">Dinas Sosial Kabupaten Minahasa</p>
                        </div>
                    </div>

                    <div className="relative">
                        <span className="inline-block rounded-full bg-[#d6b66d]/20 border border-[#d6b66d]/40 px-3.5 py-1 text-xs font-bold !text-[#e8c983] mb-4">
                            Sistem Administrasi Anak Panti
                        </span>
                        <h1 className="text-4xl font-extrabold leading-tight !text-[#fbf8f2]">
                            Pengelolaan Data Panti Asuhan &<br />
                            <span className="!text-[#e2c176]">Anak Binaan Sosial</span>
                        </h1>
                        <p className="mt-4 max-w-md text-sm leading-relaxed !text-[#fbf8f2]/90">
                            Pencatatan akurat, verifikasi legalitas dokumen instansi, rekam desil kemiskinan, serta pemetaan kebutuhan jenis bantuan sosial panti asuhan di seluruh wilayah Kabupaten Minahasa.
                        </p>
                    </div>

                    <p className="relative text-xs font-medium !text-[#fbf8f2]/70">
                        © {new Date().getFullYear()} Dinas Sosial Kabupaten Minahasa · Hak Cipta Dilindungi
                    </p>
                </section>

                {/* Panel Kanan Formulir Masuk */}
                <section className="flex items-center justify-center px-4 py-12 sm:px-8">
                    <form onSubmit={submit} className="w-full max-w-md space-y-5 rounded-3xl border border-[#e2e4d8] bg-white p-8 sm:p-10 shadow-lg">
                        <div>
                            <div className="mb-4 inline-flex items-center gap-2 rounded-xl bg-[#edf1e4] px-3 py-1.5 text-xs font-bold !text-[#203b2e] lg:hidden">
                                <Icon name="building" size={15} />
                                <span>SADAP · Dinsos Minahasa</span>
                            </div>
                            <h2 className="text-2xl sm:text-3xl font-extrabold !text-[#172b25]">Masuk ke Sistem</h2>
                            <p className="mt-1.5 text-sm !text-[#454f40] leading-relaxed">
                                Masukkan email dan kata sandi yang telah didaftarkan oleh administrator.
                            </p>
                        </div>

                        <div className="space-y-1.5">
                            <label htmlFor="email" className="block text-sm font-bold !text-[#172b25]">
                                Alamat Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="username"
                                autoFocus
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="nama@dinsos.go.id"
                                /* Memaksa focus ring menjadi hijau gelap, menghilangkan biru default */
                                className={`${inputClass} focus:border-[#203b2e] focus:ring-1 focus:ring-[#203b2e] outline-none`}
                                required
                            />
                            {errors.email && (
                                <p className="mt-1 flex items-center gap-1 text-xs font-semibold !text-[#b33a3a]">
                                    <Icon name="alert" size={13} />
                                    <span>{errors.email}</span>
                                </p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <label htmlFor="password" className="block text-sm font-bold !text-[#172b25]">
                                Kata Sandi (Password)
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Masukkan kata sandi..."
                                    className={`${inputClass} pr-20 focus:border-[#203b2e] focus:ring-1 focus:ring-[#203b2e] outline-none`}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    className="absolute inset-y-0 right-0 px-3.5 text-xs font-bold !text-[#5a6253] hover:!text-[#172b25]"
                                >
                                    {showPassword ? 'Tutup' : 'Lihat'}
                                </button>
                            </div>
                            {errors.password && (
                                <p className="mt-1 flex items-center gap-1 text-xs font-semibold !text-[#b33a3a]">
                                    <Icon name="alert" size={13} />
                                    <span>{errors.password}</span>
                                </p>
                            )}
                        </div>

                        <label className="flex items-center gap-2.5 text-sm font-medium !text-[#333b2e] cursor-pointer pt-1">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-4.5 w-4.5 rounded border-[#c4c8b9] !text-[#203b2e] focus:ring-1 focus:ring-[#203b2e] focus:ring-offset-0"
                            />
                            <span>Ingat saya di perangkat ini</span>
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className={`${btnPrimary} w-full text-base py-3`}
                        >
                            <Icon name="check" size={18} />
                            {processing ? 'Memverifikasi...' : 'Masuk Sekarang'}
                        </button>
                    </form>
                </section>
            </main>
        </>
    );
}