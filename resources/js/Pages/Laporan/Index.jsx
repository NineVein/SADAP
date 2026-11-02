import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Icon from '../../Components/Icon';
import { Card, PageHeader, btnPrimary, btnSecondary, formatDateTime, tdClass, thClass } from '../../Components/ui';

function Summary({ label, value, icon, color = 'emerald' }) {
    return (
        <div className="rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-2xs">
            <div className="flex items-center justify-between">
                <p className="text-xs font-bold uppercase tracking-wider text-[#676f60]">{label}</p>
                {icon && <Icon name={icon} size={16} className="text-[#203b2e]" />}
            </div>
            <p className="mt-2 text-2xl sm:text-3xl font-extrabold text-[#1b2e23]">{value}</p>
        </div>
    );
}

function Breakdown({ title, data, labels, unit = '' }) {
    const entries = Object.entries(data);
    const total = entries.reduce((sum, [, n]) => sum + Number(n), 0);

    return (
        <Card title={title}>
            {entries.length === 0 ? (
                <p className="text-sm font-medium text-[#7a8172]">Belum ada data rekaman.</p>
            ) : (
                <ul className="space-y-3.5">
                    {entries.map(([key, count]) => {
                        const percent = total > 0 ? Math.round((count / total) * 100) : 0;
                        return (
                            <li key={key}>
                                <div className="mb-1.5 flex justify-between text-sm">
                                    <span className="font-semibold text-[#2a382e]">{labels[key] ?? key}</span>
                                    <span className="font-extrabold text-[#203b2e]">
                                        {count} {unit} <span className="font-medium text-xs text-[#717869]">({percent}%)</span>
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-[#eaece0]">
                                    <div
                                        className="h-full rounded-full bg-[#203b2e] transition-all duration-500"
                                        style={{ width: `${percent}%` }}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </Card>
    );
}

export default function Index({ ringkasan, perWilayah, perKategori, perPendidikan, perDesil, options, dicetak }) {
    const total = perWilayah.reduce(
        (acc, r) => ({
            panti: acc.panti + r.panti,
            panti_aktif: acc.panti_aktif + r.panti_aktif,
            tervalidasi: acc.tervalidasi + r.tervalidasi,
            anak: acc.anak + r.anak,
            laki: acc.laki + r.laki,
            perempuan: acc.perempuan + r.perempuan,
        }),
        { panti: 0, panti_aktif: 0, tervalidasi: 0, anak: 0, laki: 0, perempuan: 0 }
    );

    const desilLabels = {
        1: 'Desil 1 (Sangat Miskin)',
        2: 'Desil 2 (Miskin)',
        3: 'Desil 3 (Hampir Miskin)',
        4: 'Desil 4 (Rentan Miskin)',
        5: 'Desil 5 (Menengah Bawah)',
        6: 'Desil 6 (Menengah)',
        7: 'Desil 7 (Menengah)',
        8: 'Desil 8 (Menengah)',
        9: 'Desil 9 (Mampu)',
        10: 'Desil 10 (Sangat Mampu)',
    };

    return (
        <AppLayout>
            <Head title="Laporan Statistik & Rekapitulasi - DISKATmsa" />

            <PageHeader
                title="Laporan & Statistik Binaan Panti"
                description={`Laporan agregasi data posisi per ${formatDateTime(dicetak)}. Mengintegrasikan data panti asuhan, kepengurusan, dan desil kesejahteraan.`}
                breadcrumbs={[
                    { label: 'Laporan & Statistik' },
                ]}
                actions={
                    <>
                        <a href="/laporan/ekspor/panti" className={btnSecondary}>
                            <Icon name="download" size={16} /> Unduh CSV Panti
                        </a>
                        <a href="/laporan/ekspor/anak" className={btnSecondary}>
                            <Icon name="download" size={16} /> Unduh CSV Anak
                        </a>
                        <button type="button" onClick={() => window.print()} className={btnPrimary}>
                            <Icon name="print" size={16} /> Cetak Laporan
                        </button>
                    </>
                }
            />

            {/* Kartu Ringkasan Utama */}
            <div className="mb-7 grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-6">
                <Summary label="Total Panti" value={ringkasan.panti} icon="building" />
                <Summary label="Panti Aktif" value={ringkasan.panti_aktif} icon="check" />
                <Summary label="Tervalidasi" value={ringkasan.tervalidasi} icon="shield" />
                <Summary label="Anak Binaan" value={ringkasan.anak} icon="users" />
                <Summary label="Laki-laki" value={ringkasan.laki} />
                <Summary label="Perempuan" value={ringkasan.perempuan} />
            </div>

            {/* Tabel Wilayah Kelurahan / Desa */}
            <Card
                title="Rekapitulasi Sebaran per Kelurahan & Desa"
                description="Sebaran panti asuhan dan populasi anak binaan berdasarkan wilayah kecamatan dan kelurahan di Kabupaten Minahasa"
                padded={false}
                className="mb-7"
            >
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className={thClass}>Kelurahan / Desa</th>
                                <th className={thClass}>Kecamatan</th>
                                <th className={`${thClass} text-right`}>Total Panti</th>
                                <th className={`${thClass} text-right`}>Panti Aktif</th>
                                <th className={`${thClass} text-right`}>Tervalidasi</th>
                                <th className={`${thClass} text-right`}>Total Anak</th>
                                <th className={`${thClass} text-right`}>L</th>
                                <th className={`${thClass} text-right`}>P</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#edf0e5]">
                            {perWilayah.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-6 py-12 text-center text-sm font-medium text-[#7a8172]">
                                        Belum ada data wilayah yang tercatat.
                                    </td>
                                </tr>
                            ) : (
                                perWilayah.map((r, i) => (
                                    <tr key={i} className="hover:bg-[#fbfcf9] transition">
                                        <td className={`${tdClass} font-bold text-[#1b2e23]`}>{r.kelurahan}</td>
                                        <td className={`${tdClass} font-medium text-[#5c6455]`}>{r.kecamatan}</td>
                                        <td className={`${tdClass} text-right font-extrabold text-[#203b2e]`}>{r.panti}</td>
                                        <td className={`${tdClass} text-right font-semibold text-emerald-800`}>{r.panti_aktif}</td>
                                        <td className={`${tdClass} text-right font-semibold text-[#203b2e]`}>{r.tervalidasi}</td>
                                        <td className={`${tdClass} text-right font-extrabold text-[#1b2e23]`}>{r.anak}</td>
                                        <td className={`${tdClass} text-right text-xs font-semibold text-[#656c5e]`}>{r.laki}</td>
                                        <td className={`${tdClass} text-right text-xs font-semibold text-[#656c5e]`}>{r.perempuan}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                        {perWilayah.length > 0 && (
                            <tfoot className="border-t-2 border-[#d3d6c8] bg-[#f9faf6] font-extrabold text-sm text-[#1b2e23]">
                                <tr>
                                    <td className="px-6 py-4" colSpan={2}>TOTAL KESELURUHAN</td>
                                    <td className="px-6 py-4 text-right">{total.panti}</td>
                                    <td className="px-6 py-4 text-right text-emerald-800">{total.panti_aktif}</td>
                                    <td className="px-6 py-4 text-right text-[#203b2e]">{total.tervalidasi}</td>
                                    <td className="px-6 py-4 text-right">{total.anak}</td>
                                    <td className="px-6 py-4 text-right">{total.laki}</td>
                                    <td className="px-6 py-4 text-right">{total.perempuan}</td>
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>
            </Card>

            {/* Visualisasi Distribusi Statistik */}
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Breakdown title="Distribusi Kategori Asuhan" data={perKategori} labels={options.kategori} unit="anak" />
                <Breakdown title="Jenjang Pendidikan Anak" data={perPendidikan} labels={options.pendidikan} unit="anak" />
                <Breakdown title="Sebaran Desil Kesejahteraan" data={perDesil} labels={desilLabels} unit="anak" />
            </div>
        </AppLayout>
    );
}
