import { Link } from '@inertiajs/react';
import Icon from '../../../Components/Icon';
import Badge from '../../../Components/Badge';
import {
    EmptyRow, actDelete, actEdit,
    tdClass, thClass,
} from '../../../Components/ui';

/**
 * Tab Klien LKS (Anak, Lanjut Usia, atau Disabilitas sesuai kategori LKS).
 */
export default function AnakTab({ panti, anaks = [], options = {}, can = {}, onRequestDelete }) {
    return (
        <>
            <table className="w-full text-sm">
                <thead>
                    <tr>
                        <th className={thClass}>Nama Lengkap & NIK</th>
                        <th className={thClass}>Jenis Kelamin & Umur</th>
                        <th className={thClass}>Latar Belakang & Kondisi</th>
                        <th className={thClass}>Status</th>
                        <th className={thClass}>Aksi Tindakan</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {anaks.length === 0 ? (
                        <EmptyRow colSpan={5}>Belum ada klien yang terdaftar di LKS ini.</EmptyRow>
                    ) : (
                        anaks.map((a) => {
                            return (
                                <tr key={a.id} className="hover:bg-[#fbfcf9] transition">
                                    <td className={tdClass}>
                                        <p className="font-bold text-[#1e2f24]">{a.nama}</p>
                                        <p className="text-xs text-[#707869] mt-0.5">{a.nik_tersamar ? `NIK: ${a.nik_tersamar}` : 'Belum ada NIK'}</p>
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
                                        {a.jenis_disabilitas && (
                                            <p className="mt-1 text-xs font-semibold text-violet-800">
                                                {options.jenisDisabilitas?.[a.jenis_disabilitas] ?? a.jenis_disabilitas}
                                            </p>
                                        )}
                                        {a.tingkat_ketergantungan && (
                                            <p className="mt-0.5 text-xs text-[#757e70]">
                                                {options.tingkat?.[a.tingkat_ketergantungan] ?? a.tingkat_ketergantungan}
                                            </p>
                                        )}
                                    </td>
                                    <td className={tdClass}>
                                        <Badge s={a.status} />
                                    </td>
                                    <td className={tdClass}>
                                        <div className="flex flex-wrap gap-2">
                                            {can.manage && (
                                                <Link href={`/klien/${a.id}/edit?dari=panti`} className={actEdit}>
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

