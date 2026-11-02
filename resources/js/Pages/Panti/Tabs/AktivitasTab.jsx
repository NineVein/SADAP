import Icon from '../../../Components/Icon';
import DataTable from '../../../Components/DataTable';
import { formatDateTime } from '../../../Utils/format';

/**
 * Tab Aktivitas Panti mengadopsi AktivitasTab dari dinas.
 * Menampilkan rekam jejak (audit trail) 50 perubahan terakhir profil panti.
 */
export default function AktivitasTab({ logs = [] }) {
    const cols = [
        [
            'Waktu Pencatatan',
            (a) => (
                <div className="flex items-center gap-1.5 text-xs font-semibold text-[#1f2e24]">
                    <Icon name="clock" size={13} className="text-[#8b9283]" />
                    <span>{a.waktu ? a.waktu : formatDateTime(a.created_at)}</span>
                </div>
            ),
        ],
        [
            'Oleh Pengguna',
            (a) => (
                <div>
                    <p className="font-bold text-[#1e2f24]">{a.oleh || a.user?.name || 'Sistem'}</p>
                    {a.user?.role?.name && (
                        <span className="text-[11px] font-medium text-[#737c6d]">{a.user.role.name}</span>
                    )}
                </div>
            ),
        ],
        [
            'Tindakan / Aksi',
            (a) => {
                const aksi = a.aksi || '';
                const isDelete = aksi.includes('hapus');
                const isAdd = aksi.includes('buat') || aksi.includes('unggah');
                const badgeStyle = isDelete
                    ? 'bg-rose-100 text-rose-900 border-rose-300'
                    : isAdd
                    ? 'bg-emerald-100 text-emerald-900 border-emerald-300'
                    : 'bg-amber-100 text-amber-900 border-amber-300';

                return (
                    <span className={`inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-bold ${badgeStyle}`}>
                        {aksi}
                    </span>
                );
            },
        ],
        [
            'Keterangan Perubahan',
            (a) => (
                <p className="text-sm font-medium text-[#2d3a30] leading-snug">
                    {a.deskripsi || (Array.isArray(a.bidang) ? a.bidang.join(', ') : '-')}
                </p>
            ),
        ],
    ];

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-[#eceee2] bg-[#fbfcf9] p-4 text-xs font-medium text-[#656d5e]">
                <div className="flex items-center gap-2 font-bold text-[#203b2e] mb-1">
                    <Icon name="shield" size={15} />
                    <span>Jejak Audit Perubahan Profil Panti</span>
                </div>
                <p>
                    Menampilkan maksimal 50 perubahan terakhir pada profil, anak asuh, pengurus, dan dokumen panti ini
                    sebagai pertanggungjawaban data kepada Dinas Sosial.
                </p>
            </div>

            <DataTable
                rows={logs}
                cols={cols}
                emptyText="Belum ada riwayat aktivitas yang tercatat untuk panti ini."
            />
        </div>
    );
}

