import { router } from '@inertiajs/react';

/**
 * Service pemusatan logika panti mengadopsi pola pantiService dinas.
 * Menghilangkan perulangan pemanggilan rute dan parameter di komponen tampilan.
 */
export const pantiService = {
    /**
     * Perbarui data profil panti.
     */
    perbarui(pantiId, data, options = {}) {
        router.put(`/panti/${pantiId}`, data, {
            preserveScroll: true,
            ...options,
        });
    },

    /**
     * Simpan / sinkronisasi daftar kebutuhan jenis bantuan panti.
     */
    simpanJenisBantuan(pantiId, items, options = {}) {
        const payload = {
            items: items
                .filter((x) => x.dibutuhkan)
                .map((x) => ({
                    jenis_bantuan_id: x.jenis_bantuan_id || x.id,
                    catatan: x.catatan || null,
                })),
        };

        router.put(`/panti/${pantiId}/jenis-bantuan`, payload, {
            preserveScroll: true,
            ...options,
        });
    },

    /**
     * Ajukan validasi panti ke Dinas Sosial.
     */
    ajukanValidasi(pantiId, options = {}) {
        router.post(`/panti/${pantiId}/ajukan`, {}, {
            preserveScroll: true,
            ...options,
        });
    },

    /**
     * Setujui / validasi panti oleh Supervisor / Admin.
     */
    setujuiValidasi(pantiId, options = {}) {
        router.post(`/panti/${pantiId}/setujui`, {}, {
            preserveScroll: true,
            ...options,
        });
    },

    /**
     * Tolak validasi panti dengan catatan perbaikan.
     */
    tolakValidasi(pantiId, catatan, options = {}) {
        router.post(`/panti/${pantiId}/tolak`, { catatan }, {
            preserveScroll: true,
            ...options,
        });
    },

    /**
     * Hapus data panti.
     */
    hapus(pantiId, options = {}) {
        router.delete(`/panti/${pantiId}`, options);
    },
};

export default pantiService;

