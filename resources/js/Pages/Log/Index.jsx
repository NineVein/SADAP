import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminTabs from '../../Components/AdminTabs';
import useFilters from '../../Hooks/useFilters';
import { Card, EmptyRow, FilterBar, PageHeader, Pagination, formatDateTime, inputClass, tdClass, thClass } from '../../Components/ui';

export default function Index({ logs, filters }) {
    const [f, setF] = useFilters('/log-aktivitas', filters);

    return (
        <AppLayout>
            <AdminTabs />
            <Head title="Log Aktivitas & Audit - DISKATmsa" />

            <PageHeader
                title="Log Jejak Audit & Aktivitas Sistem"
                description="Rekam jejak setiap perubahan data panti, mutasi anak asuh, pengunggahan dokumen legalitas, dan aksi validasi petugas."
                breadcrumbs={[
                    { label: 'Pengaturan Sistem', href: '/users' },
                    { label: 'Log Aktivitas' },
                ]}
            />

            <FilterBar hasActiveFilters={Boolean(f.search)} onReset={() => setF('search', '')}>
                <div className="sm:col-span-4">
                    <label htmlFor="cari" className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#525a4a]">
                        Pencarian Catatan Aktivitas
                    </label>
                    <input
                        id="cari"
                        type="search"
                        value={f.search}
                        onChange={(e) => setF('search', e.target.value)}
                        placeholder="Ketik deskripsi aktivitas, nama aksi, atau kata kunci..."
                        className={inputClass}
                    />
                </div>
            </FilterBar>

            <Card padded={false}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Waktu Kejadian</th>
                                <th className={thClass}>Nama Petugas / Pengguna</th>
                                <th className={thClass}>Deskripsi Perubahan Aktivitas</th>
                                <th className={thClass}>Alamat IP Komputer</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {logs.data.length === 0 ? (
                                <EmptyRow colSpan={4} onReset={() => setF('search', '')}>
                                    Belum ada log aktivitas yang cocok dengan pencarian.
                                </EmptyRow>
                            ) : (
                                logs.data.map((l) => (
                                    <tr key={l.id} className="hover:bg-[#fbfcf9] transition">
                                        <td className={`${tdClass} whitespace-nowrap font-bold text-xs text-[#525a4a]`}>
                                            {formatDateTime(l.created_at)}
                                        </td>
                                        <td className={`${tdClass} font-bold text-[#1b2e23]`}>
                                            {l.user?.name ?? 'Sistem Otomatis'}
                                        </td>
                                        <td className={tdClass}>
                                            <p className="font-medium text-[#203126] leading-relaxed">{l.deskripsi}</p>
                                            <span className="inline-block mt-1 rounded bg-[#eef1e6] px-2 py-0.5 text-[11px] font-bold text-[#203b2e]">
                                                {l.aksi}
                                            </span>
                                        </td>
                                        <td className={`${tdClass} text-xs font-mono text-[#6c7465]`}>
                                            {l.ip_address || '-'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={logs} />
            </Card>
        </AppLayout>
    );
}
