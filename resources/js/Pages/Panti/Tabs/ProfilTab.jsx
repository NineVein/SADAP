import { Link } from '@inertiajs/react';
import Icon from '../../../Components/Icon';
import Badge from '../../../Components/Badge';
import { actEdit, formatDateTime } from '../../../Components/ui';

function DetailItem({ label, value, children }) {
    return (
        <div className="flex flex-col rounded-xl border border-[#e2e4d8] bg-[#f7f8f3]/40 p-4 transition-colors hover:bg-[#f7f8f3] hover:border-[#c4c8b9]">
            <dt className="mb-1 text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">
                {label}
            </dt>
            <dd className="text-sm font-bold text-[#172b25] leading-relaxed">
                {children || value || <span className="text-[#8a9182] font-medium italic">Tidak ada data</span>}
            </dd>
        </div>
    );
}

/**
 * Tab Ringkasan & Profil Panti (Desain Dirapikan)
 */
export default function ProfilTab({ panti, can = {} }) {
    const kel = panti.kelurahan;

    return (
        <div className="space-y-6">
            {/* Kartu Statistik Ringkas */}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div className="flex flex-col justify-between rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-sm">
                    <p className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Anak Binaan Aktif</p>
                    <div className="mt-3">
                        <p className="text-3xl font-black text-[#172b25]">{panti.anaks_count ?? 0}</p>
                        <p className="mt-0.5 text-xs font-semibold text-[#8a9182]">Anak terdaftar</p>
                    </div>
                </div>
                <div className="flex flex-col justify-between rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-sm">
                    <p className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Pengurus Panti</p>
                    <div className="mt-3">
                        <p className="text-3xl font-black text-[#172b25]">{panti.pengurus_count ?? 0}</p>
                        <p className="mt-0.5 text-xs font-semibold text-[#8a9182]">Orang pengurus</p>
                    </div>
                </div>
                <div className="flex flex-col justify-between rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-sm">
                    <p className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Dokumen Instansi</p>
                    <div className="mt-3">
                        <p className="text-3xl font-black text-[#172b25]">{panti.dokumens_count ?? 0}</p>
                        <p className="mt-0.5 text-xs font-semibold text-[#8a9182]">Berkas legalitas</p>
                    </div>
                </div>
                <div className="flex flex-col justify-between rounded-2xl border border-[#e2e4d8] bg-white p-5 shadow-sm">
                    <p className="text-[11px] font-extrabold uppercase tracking-widest text-[#5a6253]">Status Akreditasi</p>
                    <div className="mt-3">
                        <Badge s={panti.status_akreditasi || 'Belum Terakreditasi'} />
                        <p className="mt-2 text-xs font-semibold text-[#8a9182]">Kemensos RI</p>
                    </div>
                </div>
            </div>

            {/* Rincian Profil Utama */}
            <div className="rounded-2xl border border-[#e2e4d8] bg-white p-6 shadow-sm">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[#e2e4d8] pb-4">
                    <div>
                        <h3 className="text-lg font-extrabold text-[#172b25]">Informasi Umum Panti</h3>
                        <p className="mt-0.5 text-sm text-[#5a6253]">Data identitas lembaga dan kontak operasional</p>
                    </div>
                    {can.update && (
                        <Link href={`/panti/${panti.id}/edit`} className={actEdit}>
                            <Icon name="pencil" size={16} />
                            <span>Ubah Data Profil</span>
                        </Link>
                    )}
                </div>

                {/* Grid Rincian Data */}
                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <DetailItem label="Nama Resmi Lembaga" value={panti.nama} />
                    
                    <DetailItem label="Wilayah Kelurahan / Kecamatan">
                        {kel ? (
                            <span>
                                {kel.tipe ? `${kel.tipe.toUpperCase()} ` : ''}
                                {kel.nama}, Kec. {kel.kecamatan}
                            </span>
                        ) : (
                            <span className="text-[#b08935] font-bold italic">Belum ditentukan</span>
                        )}
                    </DetailItem>
                    
                    <DetailItem label="Status Operasional">
                        <div className="pt-0.5">
                            <Badge s={panti.status || 'aktif'} />
                        </div>
                    </DetailItem>

                    <DetailItem label="Nomor Izin Operasional / SK" value={panti.no_sk || panti.nomor_izin} />
                    <DetailItem label="Tanggal Berdiri" value={panti.tanggal_berdiri} />
                    <DetailItem label="Kapasitas Asuhan" value={panti.kapasitas ? `${panti.kapasitas} Orang` : null} />
                    
                    <DetailItem label="Pimpinan / Pengelola" value={panti.nama_pengelola} />
                    <DetailItem label="Nomor Telepon" value={panti.telepon} />
                    <DetailItem label="Alamat Surel (Email)" value={panti.email} />
                    
                    {/* Alamat dibuat memanjang memenuhi semua kolom yang tersedia */}
                    <div className="sm:col-span-2 lg:col-span-3">
                        <DetailItem label="Alamat Lengkap" value={panti.alamat} />
                    </div>
                </dl>

                {/* Footer Timestamp */}
                <div className="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-[#f7f8f3] px-4 py-3 text-xs font-semibold text-[#5a6253]">
                    <span className="flex items-center gap-1.5">
                        <Icon name="clock" size={14} className="text-[#8a9182]" />
                        Terdaftar sejak: {formatDateTime(panti.created_at)}
                    </span>
                    {panti.updated_at && (
                        <span className="flex items-center gap-1.5">
                            <Icon name="check" size={14} className="text-[#8a9182]" />
                            Pembaruan terakhir: {formatDateTime(panti.updated_at)}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}