import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Icon from './Icon';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(true);
    const message = flash?.success || flash?.error;
    const isError = Boolean(flash?.error);

    useEffect(() => {
        setVisible(true);
        if (!message) return;
        // Memberikan waktu 8 detik agar pengguna lanjut usia sempat membaca pesan secara menyeluruh
        const timer = setTimeout(() => setVisible(false), 8000);
        return () => clearTimeout(timer);
    }, [message]);

    if (!message || !visible) return null;

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={`mb-7 flex items-start gap-3.5 rounded-2xl border px-5 py-4 text-sm shadow-sm transition-all print:hidden ${
                isError
                    ? 'border-rose-300 bg-rose-50/95 text-rose-900 shadow-rose-100'
                    : 'border-emerald-300 bg-emerald-50/95 text-emerald-950 shadow-emerald-100'
            }`}
        >
            <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${
                isError ? 'bg-rose-200/70 text-rose-800' : 'bg-emerald-200/70 text-emerald-800'
            }`}>
                <Icon name={isError ? 'alert' : 'check'} size={19} />
            </div>
            <div className="flex-1 pt-0.5 min-w-0">
                <p className="font-bold text-xs uppercase tracking-wider mb-0.5">
                    {isError ? 'Pemberitahuan / Kendala' : 'Berhasil Dilakukan'}
                </p>
                <p className="text-sm font-medium leading-relaxed">{message}</p>
            </div>
            <button
                type="button"
                onClick={() => setVisible(false)}
                aria-label="Tutup pesan pemberitahuan"
                className={`rounded-lg p-1.5 transition ${
                    isError ? 'hover:bg-rose-200/60 text-rose-700' : 'hover:bg-emerald-200/60 text-emerald-700'
                }`}
            >
                <Icon name="close" size={18} />
            </button>
        </div>
    );
}
