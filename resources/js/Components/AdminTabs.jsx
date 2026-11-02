import { Link, usePage } from '@inertiajs/react';
import Icon from './Icon';

const tabs = [
    { label: 'Pengguna Sistem', href: '/users', icon: 'shield' },
    { label: 'Master Wilayah Kelurahan', href: '/kelurahan', icon: 'building' },
    { label: 'Jejak Audit & Log Aktivitas', href: '/log-aktivitas', icon: 'report' },
];

export default function AdminTabs() {
    const path = usePage().url.split('?')[0];

    return (
        <nav aria-label="Navigasi Pengaturan Sistem" className="mb-7 flex flex-wrap gap-2 border-b border-[#e5e7dc] bg-[#fbfcf9] p-2 rounded-2xl print:hidden">
            {tabs.map((t) => {
                const active = path === t.href || path.startsWith(`${t.href}/`);
                return (
                    <Link
                        key={t.href}
                        href={t.href}
                        aria-current={active ? 'page' : undefined}
                        className={`flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition ${
                            active
                                ? 'bg-[#203b2e] text-[#f7f2e5] shadow-xs'
                                : 'text-[#5a6253] hover:bg-[#eff1e6] hover:text-[#1b2e23]'
                        }`}
                    >
                        <Icon name={t.icon} size={16} />
                        <span>{t.label}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
