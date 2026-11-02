import { thClass, tdClass } from './ui';

/**
 * Komponen DataTable generik mengadopsi pola DataTable dinas.
 * Menghilangkan perulangan markup <table>, <thead>, <tbody> di seluruh halaman.
 *
 * @param {Array} rows - Array data baris
 * @param {Array} cols - Array definisi kolom: [ ['Header Label', (row) => cellContent] ]
 * @param {Function} onRow - Callback klik baris opsional
 * @param {String} emptyText - Pesan saat data kosong
 */
export default function DataTable({ rows, cols, onRow, emptyText = 'Tidak ada data yang tersedia.' }) {
    if (!rows?.length) {
        return (
            <div className="rounded-2xl border border-dashed border-[#dce0d2] bg-[#fbfcf9] p-8 text-center text-sm font-medium text-[#7d8475]">
                {emptyText}
            </div>
        );
    }

    const resolvedCols =
        cols ||
        Object.keys(rows[0])
            .filter((k) => typeof rows[0][k] !== 'object' || rows[0][k] === null)
            .map((k) => [k.replaceAll('_', ' '), (r) => r[k]]);

    return (
        <div className="overflow-x-auto rounded-2xl border border-[#e4e7db] bg-white shadow-2xs">
            <table className="w-full text-sm">
                <thead>
                    <tr>
                        {resolvedCols.map((c, i) => (
                            <th key={c[0] || i} className={thClass}>
                                {c[0]}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-[#edf0e5]">
                    {rows.map((r, i) => (
                        <tr
                            key={r.id ?? i}
                            onClick={onRow ? () => onRow(r) : undefined}
                            className={`transition ${onRow ? 'cursor-pointer hover:bg-[#f4f7ee]' : 'hover:bg-[#fbfcf9]'}`}
                        >
                            {resolvedCols.map((c, j) => (
                                <td key={c[0] || j} className={tdClass}>
                                    {typeof c[1] === 'function' ? c[1](r) : r[c[0]]}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

