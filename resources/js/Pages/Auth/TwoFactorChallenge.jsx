import { useEffect, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Icon from '../../Components/Icon';
import { btnPrimary, inputClass } from '../../Components/ui';

export default function TwoFactorChallenge({ email, sisaJeda = 0 }) {
    const { errors: galatHalaman } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({ kode: '' });
    const [jeda, setJeda] = useState(sisaJeda);
    const [mengirim, setMengirim] = useState(false);
    const [terkirim, setTerkirim] = useState(false);

    // Hitung mundur sebelum tombol kirim ulang aktif lagi.
    useEffect(() => {
        if (jeda <= 0) return undefined;
        const t = setTimeout(() => setJeda((v) => v - 1), 1000);
        return () => clearTimeout(t);
    }, [jeda]);

    function submit(e) {
        e.preventDefault();
        post('/verifikasi-2fa', { onFinish: () => reset('kode') });
    }

    function kirimUlang() {
        setMengirim(true);
        router.post('/verifikasi-2fa/kirim-ulang', {}, {
            preserveScroll: true,
            onSuccess: () => { setJeda(60); setTerkirim(true); },
            onFinish: () => setMengirim(false),
        });
    }

    return (
        <>
            <Head title="Verifikasi Dua Langkah - DISKATmsa" />

            <main className="flex min-h-screen items-center justify-center bg-[#f7f8f3] px-4 py-12">
                <form onSubmit={submit} className="w-full max-w-md space-y-6 rounded-2xl border border-[#e2e4d8] bg-white p-8 shadow-sm">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#203b2e] text-[#e0c27c]">
                        <Icon name="shield" size={24} />
                    </div>

                    <div>
                        <h1 className="text-2xl font-extrabold text-[#172b25]">Verifikasi Dua Langkah</h1>
                        <p className="mt-2 text-sm font-medium leading-6 text-[#5f685a]">
                            Kami mengirim kode 6 digit ke <strong className="text-[#172b25]">{email}</strong>. Buka email Anda lalu masukkan kodenya di sini. Kode berlaku 10 menit.
                        </p>
                    </div>

                    {terkirim && !galatHalaman?.kode && <p className="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Kode baru sudah dikirim. Kode sebelumnya tidak berlaku lagi.</p>}

                    <div>
                        <label htmlFor="kode" className="mb-1.5 block text-sm font-bold text-[#2d3b31]">Kode 6 digit</label>
                        <input
                            id="kode"
                            autoFocus
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={7}
                            value={data.kode}
                            onChange={(e) => setData('kode', e.target.value.replace(/[^\d\s]/g, ''))}
                            placeholder="000000"
                            className={`${inputClass} text-center font-mono text-xl tracking-[0.4em]`}
                        />
                    </div>

                    {(errors.kode || galatHalaman?.kode) && <p className="text-sm font-semibold text-rose-700">{errors.kode || galatHalaman.kode}</p>}

                    <button
                        type="submit"
                        disabled={processing || data.kode.replace(/\D/g, '').length < 6}
                        className={`${btnPrimary} w-full py-3`}
                    >
                        {processing ? 'Memeriksa...' : 'Verifikasi dan Masuk'}
                    </button>

                    <div className="flex items-center justify-between text-xs font-bold">
                        <button
                            type="button"
                            onClick={kirimUlang}
                            disabled={jeda > 0 || mengirim}
                            className="text-[#7a5d1c] hover:underline disabled:cursor-not-allowed disabled:text-[#a3a899] disabled:no-underline"
                        >
                            {jeda > 0 ? `Kirim ulang kode (${jeda} dtk)` : mengirim ? 'Mengirim...' : 'Kirim ulang kode'}
                        </button>
                        <button type="button" onClick={() => router.post('/verifikasi-2fa/batal')} className="text-[#6c7465] hover:underline">
                            Batal, kembali ke halaman masuk
                        </button>
                    </div>

                    <p className="text-xs leading-5 text-[#6c7465]">
                        Email tidak masuk? Periksa folder spam. Bila Anda tidak bisa lagi membuka email tersebut, minta administrator mereset verifikasi dua langkah akun Anda.
                    </p>
                </form>
            </main>
        </>
    );
}
