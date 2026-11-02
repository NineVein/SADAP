import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Icon from '../Components/Icon';
import FlashMessage from '../Components/FlashMessage';

function buildNavigation(role) {
    const groups = [
        {
            section: 'Menu Utama',
            items: [{ label: 'Dashboard Utama', href: '/', icon: 'dashboard' }],
        },
        {
            section: 'Pengelolaan Panti & Bantuan',
            items: [
                // Panti = pusat kerja: anak, pengurus, dokumen, dan validasi dikerjakan dari sini.
                { label: 'Panti Asuhan', href: '/panti', icon: 'building', badge: 'validasi' },
                { label: 'Anak & Desil', href: '/anak', icon: 'users', badge: 'desil' },
                { label: 'Laporan & Statistik', href: '/laporan', icon: 'report' },
            ],
        },
    ];

    if (role === 'admin') {
        groups.push({
            section: 'Pengaturan Sistem',
            items: [
                { label: 'Pengaturan & Pengguna', href: '/users', icon: 'shield', match: ['/users', '/kelurahan', '/log-aktivitas'] },
            ],
        });
    }

    return groups;
}

function BrandMark() {
    return (
        <div className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#203b2e] text-[#e0c27c] shadow-md shadow-[#203b2e]/20">
            <svg viewBox="0 0 40 40" className="h-7 w-7" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M20 31V10" />
                <path d="M20 20C12 20 9 15 9 10c7 0 11 4 11 10Z" />
                <path d="M20 25c8 0 11-5 11-10-7 0-11 4-11 10Z" />
                <path d="M13 32h14" />
            </svg>
        </div>
    );
}

function SidebarContent({ user, badges, path, onNavigate }) {
    const navigation = buildNavigation(user?.role);

    const isActive = (item) => {
        const hrefs = item.match ?? [item.href];
        return hrefs.some((h) => (h === '/' ? path === '/' : path === h || path.startsWith(`${h}/`)));
    };

    return (
        <>
            <div className="px-6 pb-6 pt-7">
                <Link href="/" className="flex items-center gap-3.5" onClick={onNavigate}>
                    <BrandMark />
                    <div className="min-w-0">
                        <p className="text-[20px] font-black tracking-[-0.04em] text-[#1e2f25]">
                            DISKAT<span className="text-[#b49550]">msa</span>
                        </p>
                        <p className="text-xs font-semibold text-[#666c5e]">Sistem Panti · Dinsos</p>
                    </div>
                </Link>
            </div>

            <div className="mx-6 h-px bg-[#e8eae0]" />

            <nav aria-label="Navigasi utama" className="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                {navigation.map((group) => (
                    <div key={group.section}>
                        <p className="mb-2.5 px-3 text-xs font-bold uppercase tracking-wider text-[#818778]">
                            {group.section}
                        </p>
                        <div className="space-y-1.5">
                            {group.items.map((item) => {
                                const active = isActive(item);
                                const count = item.badge ? badges?.[item.badge] : 0;

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={onNavigate}
                                        aria-current={active ? 'page' : undefined}
                                        className={`group flex min-h-[44px] items-center gap-3.5 rounded-xl px-3.5 py-2.5 text-sm transition-all ${
                                            active
                                                ? 'bg-[#203b2e] font-bold text-[#f7f2e5] shadow-sm'
                                                : 'font-semibold text-[#485347] hover:bg-[#eff1e6] hover:text-[#1b2e23]'
                                        }`}
                                    >
                                        <span className={`shrink-0 transition ${active ? 'text-[#e0c27c]' : 'text-[#6e776a] group-hover:text-[#203b2e]'}`}>
                                            <Icon name={item.icon} size={20} />
                                        </span>
                                        <span className="flex-1 truncate">{item.label}</span>
                                        {count > 0 && (
                                            <span className="rounded-full bg-[#b49550] px-2.5 py-0.5 text-xs font-extrabold text-white shadow-2xs">
                                                {count}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="border-t border-[#e8eae0] p-4 bg-[#fbfcf9]">
                <div className="rounded-2xl border border-[#e4e6d9] bg-white p-3.5 shadow-2xs">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8dfc8] text-base font-extrabold text-[#203b2e] shadow-inner">
                            {(user?.name ?? 'A').trim().charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-bold text-[#1f2e24]">{user?.name}</p>
                            <p className="truncate text-xs font-semibold text-[#737b6c]">
                                {user?.role === 'operator' && user?.kelurahan ? user.kelurahan : user?.role_label}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3.5 grid grid-cols-2 gap-2">
                        <Link
                            href="/profil"
                            onClick={onNavigate}
                            className="flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-[#d6d9cb] bg-white py-1.5 text-xs font-bold text-[#2d3b31] hover:bg-[#f1f3e9] transition"
                        >
                            <Icon name="key" size={14} /> Password
                        </Link>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/60 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition"
                        >
                            <Icon name="logout" size={14} /> Keluar
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function AppLayout({ children }) {
    const { url, props } = usePage();
    const [drawer, setDrawer] = useState(false);
    const user = props.auth?.user;
    const path = url.split('?')[0];

    // Tutup menu seluler setiap kali pindah halaman.
    useEffect(() => setDrawer(false), [path]);

    return (
        <div className="min-h-screen bg-[#f7f8f3] text-[#203126]">
            {/* SIDEBAR DESKTOP */}
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-[275px] flex-col border-r border-[#e3e6d8] bg-[#fffefa] lg:flex print:hidden shadow-xs">
                <SidebarContent user={user} badges={props.badges} path={path} />
            </aside>

            {/* DRAWER SELULER */}
            {drawer && (
                <div className="fixed inset-0 z-50 lg:hidden print:hidden">
                    <div className="absolute inset-0 bg-black/50 backdrop-blur-xs" onClick={() => setDrawer(false)} />
                    <aside className="absolute inset-y-0 left-0 flex w-[290px] max-w-[85vw] flex-col bg-[#fffefa] shadow-2xl">
                        <button
                            type="button"
                            onClick={() => setDrawer(false)}
                            aria-label="Tutup menu samping"
                            className="absolute right-3.5 top-4 rounded-xl p-2 text-[#575e51] hover:bg-[#eff1e6]"
                        >
                            <Icon name="close" size={22} />
                        </button>
                        <SidebarContent user={user} badges={props.badges} path={path} onNavigate={() => setDrawer(false)} />
                    </aside>
                </div>
            )}

            <div className="min-w-0 lg:pl-[275px] print:pl-0">
                <header className="sticky top-0 z-30 flex min-h-[68px] items-center gap-3 border-b border-[#e3e6d8] bg-[#fffefa]/95 px-4 backdrop-blur-xl sm:px-8 xl:px-10 print:hidden shadow-2xs">
                    <button
                        type="button"
                        onClick={() => setDrawer(true)}
                        aria-label="Buka menu samping"
                        className="rounded-xl p-2.5 text-[#24352a] hover:bg-[#eff1e6] lg:hidden"
                    >
                        <Icon name="menu" size={24} />
                    </button>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-base font-extrabold text-[#1a2c20]">
                            DISKATmsa · Sistem Administrasi dan Data Anak Panti
                        </p>
                        <p className="truncate text-xs font-semibold text-[#666e5f]">Dinas Sosial Kabupaten Minahasa</p>
                    </div>
                </header>

                <main className="px-4 py-8 sm:px-8 sm:py-9 xl:px-10">
                    <div className="mx-auto w-full max-w-[1400px]">
                        <FlashMessage />
                        {children}
                    </div>
                </main>

                <footer className="px-4 pb-8 sm:px-8 xl:px-10 print:hidden">
                    <div className="mx-auto max-w-[1400px] border-t border-[#e3e6d8] pt-5 flex flex-wrap items-center justify-between gap-3 text-xs font-medium text-[#7a8172]">
                        <p>© {new Date().getFullYear()} DISKATmsa · Dinas Sosial Kabupaten Minahasa. Hak Cipta Dilindungi.</p>
                        <p>Sistem Terintegrasi Panti Asuhan & Penyaluran Bantuan Sosial</p>
                    </div>
                </footer>
            </div>
        </div>
    );
}
