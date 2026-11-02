import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Icon from '../../../Components/Icon';
import Badge from '../../../Components/Badge';
import DesilForm from '../../../Components/DesilForm';
import {
    EmptyRow, StatusBadge, actDelete, actEdit, actView,
    tdClass, thClass,
} from '../../../Components/ui';

/**
 * Tab Anak Asuh / Binaan panti.
 */
export default function AnakTab({ panti, anaks = [], options = {}, can = {}, onRequestDelete }) {
    const [desilTarget, setDesilTarget] = useState(null);

    return (
        <>
            {desilTarget && (
                <div className="px-6 pt-6">
                    <DesilForm
                        key={desilTarget.id}
                        anak={desilTarget}
                        pantiName={panti.nama}
                        options={options}
                        isReviewer={can.reviewDesil}
                        onDone={() => setDesilTarget(null)}
                    />
                </div>
            )}
            <table className="w-full text-sm">
                <thead>
                    <tr>
                        <th className={thClass}>Nama Lengkap & NIK</th>
                        <th className={thClass}>Jenis Kelamin & Umur</th>
                        <th className={thClass}>Kategori Asuhan</th>
                        <th className={thClass}>Desil Kesejahteraan</th>
                        <th className={thClass}>Status</th>
                        <th className={thClass}>Aksi Tindakan</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {anaks.length === 0 ? (
                        <EmptyRow colSpan={6}>Belum ada data anak asuh yang terdaftar di panti ini.</EmptyRow>
                    ) : (
                        anaks.map((a) => {
                            const menunggu = a.laporan?.status === 'menunggu';
                            return (
                                <tr key={a.id} className="hover:bg-[#fbfcf9] transition">
                                    <td className={tdClass}>
                                        <p className="font-bold text-[#1e2f24]">{a.nama}</p>
                                        <p className="text-xs text-[#707869] mt-0.5">{a.nik ? `NIK: ${a.nik}` : 'Belum ada NIK'}</p>
                                    </td>
                                    <td className={tdClass}>
                                        <span className="font-medium text-[#2d3a30]">
                                            {a.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}
                                        </span>
                                        <p className="text-xs text-[#757e70]">{a.umur ? `${a.umur} Tahun` : '-'}</p>
                                    </td>
                                    <td className={tdClass}>
                                        <span className="inline-block rounded-lg bg-[#eff2e7] px-2.5 py-1 text-xs font-bold text-[#203b2e]">
                                            {options.kategori?.[a.kategori] ?? a.kategori}
                                        </span>
                                    </td>
                                    <td className={tdClass}>
                                        {a.desil ? (
                                            <span className="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">
                                                Desil {a.desil}
                                            </span>
                                        ) : (
                                            <span className="text-xs font-semibold text-amber-700">Belum ada desil</span>
                                        )}
                                        {menunggu && (
                                            <p className="mt-1">
                                                <StatusBadge value="diajukan" label="Laporan Menunggu" />
                                            </p>
                                        )}
                                        {a.laporan?.status === 'ditolak' && (
                                            <p className="mt-1 text-xs font-semibold text-rose-700">
                                                Ditolak: {a.laporan.catatan_review}
                                            </p>
                                        )}
                                    </td>
                                    <td className={tdClass}>
                                        <Badge s={a.status} />
                                    </td>
                                    <td className={tdClass}>
                                        <div className="flex flex-wrap gap-2">
                                            {can.desil && !menunggu && (
                                                <button
                                                    type="button"
                                                    onClick={() => setDesilTarget(a)}
                                                    className={actView}
                                                >
                                                    {a.desil ? 'Ubah Desil' : 'Laporkan Desil'}
                                                </button>
                                            )}
                                            {can.manage && (
                                                <Link href={`/anak/${a.id}/edit?dari=panti`} className={actEdit}>
                                                    Edit
                                                </Link>
                                            )}
                                            {can.manage && (
                                                <button
                                                    type="button"
                                                    onClick={() => onRequestDelete('anak', a.id, a.nama)}
                                                    className={actDelete}
                                                >
                                                    Hapus
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            );
                        })
                    )}
                </tbody>
            </table>
        </>
    );
}

