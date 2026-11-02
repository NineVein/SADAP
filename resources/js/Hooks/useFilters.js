import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Menyimpan nilai filter dan mengirimnya ke server (dengan jeda 350 ms)
 * hanya ketika nilainya benar-benar berubah. Nilai kosong tidak dikirim.
 */
export default function useFilters(url, initial) {
    const [values, setValues] = useState(initial);
    const lastSent = useRef(JSON.stringify(initial));

    useEffect(() => {
        const key = JSON.stringify(values);
        if (key === lastSent.current) return;

        const timer = setTimeout(() => {
            lastSent.current = key;
            const query = Object.fromEntries(
                Object.entries(values).filter(([, v]) => v !== '' && v != null)
            );
            router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
        }, 350);

        return () => clearTimeout(timer);
    }, [values]);

    const set = (name, value) => setValues((current) => ({ ...current, [name]: value }));

    return [values, set];
}
