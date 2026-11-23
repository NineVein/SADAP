import { useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import { Card, Field, PageHeader, btnDanger, btnPrimary, btnSecondary, inputClass } from '../../Components/ui';

function FormNonaktif() {
    const form = useForm({ password: '' });

    function kirim(e) {
        e.preventDefault();
        if (!window.confirm('Nonaktifkan verifikasi dua langkah? Akun Anda akan lebih rentan.')) return;
        form.delete('/keamanan', { preserveScroll: true, onFinish: () => form.reset('password') });
    }

    return (
        <form onSubmit={kirim} className="space-y-3">
            <Field label="Password Anda" required error={form.errors.password} hint="Dibutuhkan untuk memastikan ini benar-benar Anda">
                <input
                    type="password"
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <button type="submit" disabled={form.processing || !form.data.password} className={btnDanger}>
                Nonaktifkan
            </button>
        </form>
    );
}

export default function Index({ aktif, wajib, emailTujuan, menungguKonfirmasi, sisaJeda = 0, aktifSejak }) {
    const konfirmasi = useForm({ kode: '' });
    const [jeda, setJeda] = useState(sisaJeda);
    const [mengirim, setMengirim] = useState(false);

    useEffect(() => {
        setJeda(sisaJeda);
    }, [sisaJeda]);

    useEffect(() => {
        if (jeda <= 0) return undefined;
        const t = setTimeout(() => setJeda((v) => v - 1), 1000);
        return () => clearTimeout(t);
    }, [jeda]);

    function kirimKode() {
        setMengirim(true);
        router.post('/keamanan/aktifkan', {}, { preserveScroll: true, onFinish: () => setMengirim(false) });
    }

    return (
        <AppLayout>
            <Head title="Keamanan Akun - DISKATmsa" />

            <PageHeader
                title="Keamanan Akun"
                description="Verifikasi dua langkah mengirim kode 6 digit ke email Anda setiap kali masuk, sehingga password yang bocor saja tidak cukup untuk membuka akun."
                breadcrumbs={[{ label: 'Keamanan Akun' }]}
            />

            <div className="max-w-3xl space-y-6">
                {wajib && !aktif && (
                    <div role="alert" className="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                        <Icon name="alert" size={18} className="mt-0.5 shrink-0" />
                        <p>Peran Anda wajib memakai verifikasi dua langkah. Selesaikan pengaturan di bawah sebelum bisa memakai menu lain.</p>
                    </div>
                )}

                <Card
                    title="Status verifikasi dua langkah"
                    actions={
                        <span className={`rounded-full px-3 py-1 text-xs font-extrabold ${aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                            {aktif ? 'Aktif' : 'Belum aktif'}
                        </span>
                    }
                >
                    {!aktif && !menungguKonfirmasi && (
                        <div className="space-y-4">
                            <p className="text-sm leading-6 text-[#4a5345]">
                                Kode akan dikirim ke email akun Anda: <strong>{emailTujuan}</strong>. Pastikan Anda bisa membuka email tersebut. Prosesnya kurang dari satu menit.
                            </p>
                            <button type="button" onClick={kirimKode} disabled={mengirim} className={btnPrimary}>
                                <Icon name="shield" size={16} /> {mengirim ? 'Mengirim...' : 'Kirim Kode ke Email'}
                            </button>
                        </div>
                    )}

                    {!aktif && menungguKonfirmasi && (
                        <div className="space-y-5">
                            <p className="text-sm leading-6 text-[#4a5345]">
                                Kode 6 digit sudah dikirim ke <strong>{emailTujuan}</strong>. Masukkan kode itu untuk menyelesaikan aktivasi. Kode berlaku 10 menit.
                            </p>

                            <form
                                onSubmit={(e) => { e.preventDefault(); konfirmasi.post('/keamanan/konfirmasi', { preserveScroll: true, onFinish: () => konfirmasi.reset('kode') }); }}
                                className="max-w-xs space-y-3"
                            >
                                <Field label="Kode 6 digit" required error={konfirmasi.errors.kode}>
                                    <input
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        maxLength={7}
                                        value={konfirmasi.data.kode}
                                        onChange={(e) => konfirmasi.setData('kode', e.target.value.replace(/[^\d\s]/g, ''))}
                                        placeholder="000000"
                                        className={`${inputClass} text-center font-mono text-lg tracking-[0.3em]`}
                                    />
                                </Field>
                                <button type="submit" disabled={konfirmasi.processing || konfirmasi.data.kode.replace(/\D/g, '').length < 6} className={btnPrimary}>
                                    Konfirmasi dan Aktifkan
                                </button>
                            </form>

                            <button type="button" onClick={kirimKode} disabled={jeda > 0 || mengirim} className={`${btnSecondary} disabled:opacity-60`}>
                                {jeda > 0 ? `Kirim ulang kode (${jeda} dtk)` : 'Kirim ulang kode'}
                            </button>
                        </div>
                    )}

                    {aktif && (
                        <p className="text-sm leading-6 text-[#4a5345]">
                            Akun Anda dilindungi. Kode masuk dikirim ke <strong>{emailTujuan}</strong>
                            {aktifSejak ? <> (aktif sejak {aktifSejak})</> : null}. Bila email itu tidak bisa dibuka lagi, minta administrator mereset verifikasi dua langkah Anda.
                        </p>
                    )}
                </Card>

                {aktif && !wajib && (
                    <Card title="Nonaktifkan verifikasi dua langkah" description="Tidak disarankan. Akun hanya dilindungi password.">
                        <FormNonaktif />
                    </Card>
                )}

                {aktif && wajib && (
                    <p className="text-xs font-semibold text-[#6c7465]">Verifikasi dua langkah tidak bisa dinonaktifkan karena diwajibkan untuk peran Anda.</p>
                )}
            </div>
        </AppLayout>
    );
}
