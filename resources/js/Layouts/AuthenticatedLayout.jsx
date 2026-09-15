import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const nav = [
    { href: '/dashboard', label: 'Overview' },
    { href: '/knowledge-bases', label: 'Knowledge bases' },
];

export default function AuthenticatedLayout({ title, actions, children }) {
    const { auth, flash } = usePage().props;
    const [clientError, setClientError] = useState('');

    useEffect(() => {
        return router.on('invalid', (event) => {
            event.preventDefault();
            const status = event.detail.response?.status ?? 'unknown';
            const payload = event.detail.response?.data;
            const extracted = typeof payload === 'string'
                ? payload.match(/<p>([^<]+)<\/p>/)?.[1]
                : payload?.message;
            setClientError(extracted || `Request failed (${status}). Check storage/logs/laravel.log.`);
        });
    }, []);

    return (
        <div className="min-h-screen bg-slate-50">
            <div className="grid min-h-screen md:grid-cols-[260px_1fr]">
                <aside className="hidden border-r border-slate-200 bg-slate-950 text-slate-200 md:flex md:flex-col">
                    <Link href="/dashboard" className="flex items-center gap-2 px-6 py-6 text-lg font-bold text-white">
                        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500">D</span>
                        DocAgent
                    </Link>
                    <nav className="flex-1 space-y-1 px-3">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="block rounded-xl px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <div className="border-t border-white/10 p-4">
                        <div className="truncate text-sm font-medium text-white">{auth.user.name}</div>
                        <div className="truncate text-xs text-slate-400">{auth.user.organization?.name}</div>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="mt-3 text-xs font-medium text-indigo-300 hover:text-white"
                        >
                            Sign out
                        </button>
                    </div>
                </aside>
                <div className="flex min-w-0 flex-col">
                    <header className="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-4 lg:px-8">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-indigo-600">
                                {auth.user.organization?.name}
                            </p>
                            <h1 className="text-xl font-bold text-slate-900">{title}</h1>
                        </div>
                        <div className="flex items-center gap-3">{actions}</div>
                    </header>
                    <main className="flex-1 px-4 py-6 lg:px-8">
                        {flash?.success && (
                            <div className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                {flash.success}
                            </div>
                        )}
                        {flash?.error && (
                            <div className="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                {flash.error}
                            </div>
                        )}
                        {clientError && (
                            <div className="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                {clientError}
                            </div>
                        )}
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}

export function StatusBadge({ status }) {
    const map = {
        pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        processing: 'bg-sky-50 text-sky-700 ring-sky-200',
        processed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        failed: 'bg-rose-50 text-rose-700 ring-rose-200',
    };

    const labels = {
        pending: 'Queued',
        processing: 'Processing',
        processed: 'Ready',
        failed: 'Failed',
    };

    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${map[status] ?? 'bg-slate-100 text-slate-700'}`}>
            {labels[status] ?? status}
        </span>
    );
}
