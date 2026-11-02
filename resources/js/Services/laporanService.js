/**
 * Service ekspor dan laporan mengadopsi pola laporanService dinas.
 */
export const laporanService = {
    /**
     * Unduh ekspor laporan dalam format CSV / Excel.
     */
    unduh(jenis, params = {}) {
        const query = new URLSearchParams(
            Object.fromEntries(
                Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')
            )
        ).toString();

        const url = `/laporan/ekspor/${jenis}${query ? `?${query}` : ''}`;
        window.location.href = url;
    },
};

export default laporanService;

