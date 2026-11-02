import { useForm } from '@inertiajs/react';
import Icon from './Icon';
import { Card, Field, btnPrimary, btnSecondary, inputClass } from './ui';

/** Formulir lapor/ubah desil. Dipakai di halaman Anak dan di detail Panti. */
export default function DesilForm({ anak, pantiName, options, isReviewer, onDone }) {
    const form = useForm({
        desil: anak.desil ?? '',
        sumber: '',
        tanggal_data: '',
        catatan: '',
    });

    function submit(e) {
        e.preventDefault();
        form.post(`/anak/${anak.id}/desil`, { preserveScroll: true, onSuccess: onDone });
    }

    return (
        <Card
            title={`${anak.desil ? 'Perbarui Usulan' : 'Laporkan Penetapan'} Desil: ${anak.nama}`}
            description={`${pantiName ? `Lembaga: ${pantiName}` : ''}${anak.desil ? ` · Desil saat ini: Desil ${anak.desil}` : ' · Belum memiliki catatan desil'}`}
            className="mb-7 border-amber-300 shadow-md"
        >
            <form onSubmit={submit} className="grid gap-5 md:grid-cols-2">
                <Field
                    label="Tingkat Desil Kesejahteraan"
                    required
                    error={form.errors.desil}
                    hint="Desil 1 (Sangat Miskin/Prioritas Tertinggi) sampai Desil 10 (Sangat Mampu)"
                >
                    <select
                        value={form.data.desil}
                        onChange={(e) => form.setData('desil', e.target.value)}
                        className={inputClass}
                        autoFocus
                    >
                        <option value="">-- Pilih Nilai Desil (1 s/d 10) --</option>
                        {Array.from({ length: 10 }, (_, i) => i + 1).map((n) => (
                            <option key={n} value={n}>
                                Desil {n} {n <= 3 ? '(Prioritas Sangat Miskin/Miskin)' : n <= 5 ? '(Rentan Miskin)' : '(Menengah/Mampu)'}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field
                    label="Sumber Dasar Penetapan Data"
                    required
                    error={form.errors.sumber}
                    hint="Dokumen atau basis data resmi penetapan desil"
                >
                    <select
                        value={form.data.sumber}
                        onChange={(e) => form.setData('sumber', e.target.value)}
                        className={inputClass}
                    >
                        <option value="">-- Pilih Sumber Rujukan Data --</option>
                        {Object.entries(options.sumber).map(([v, l]) => (
                            <option key={v} value={v}>{l}</option>
                        ))}
                    </select>
                </Field>

                <Field
                    label="Tanggal Pengambilan Data / Verifikasi"
                    error={form.errors.tanggal_data}
                    hint="Waktu ketika data desil dicek di lapangan"
                >
                    <input
                        type="date"
                        value={form.data.tanggal_data}
                        onChange={(e) => form.setData('tanggal_data', e.target.value)}
                        className={inputClass}
                    />
                </Field>

                <Field
                    label="Catatan Keterangan Pendukung"
                    error={form.errors.catatan}
                    hint="Catatan kondisi ekonomi, nama wali, atau nomor rujukan"
                >
                    <input
                        value={form.data.catatan}
                        onChange={(e) => form.setData('catatan', e.target.value)}
                        placeholder="Contoh: Sesuai data DTKS Kartu Keluarga paman/wali..."
                        className={inputClass}
                    />
                </Field>

                <div className="flex flex-wrap items-center gap-3 md:col-span-2 pt-2">
                    <button type="submit" disabled={form.processing} className={btnPrimary}>
                        <Icon name="check" size={16} />
                        {form.processing ? 'Menyimpan...' : isReviewer ? 'Simpan & Berlakukan Desil' : 'Kirim Usulan Desil ke Dinsos'}
                    </button>
                    <button type="button" onClick={onDone} className={btnSecondary}>
                        Batal
                    </button>
                </div>

                {!isReviewer && (
                    <div className="md:col-span-2 rounded-xl bg-amber-50/70 border border-amber-200/60 p-3 text-xs font-semibold text-amber-900 flex items-center gap-2">
                        <Icon name="clock" size={16} className="text-amber-700 shrink-0" />
                        <span>Usulan desil akan diverifikasi dan disetujui terlebih dahulu oleh Petugas Penilai / Supervisor Dinas Sosial sebelum resmi berlaku pada profil anak.</span>
                    </div>
                )}
            </form>
        </Card>
    );
}
