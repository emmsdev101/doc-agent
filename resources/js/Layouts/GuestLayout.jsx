import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, title, subtitle }) {
    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top,_#e0e7ff,_#f8fafc_42%)]">
            <div className="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
                <Link href="/" className="flex items-center gap-2 text-lg font-bold text-slate-900">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        D
                    </span>
                    DocAgent
                </Link>
                <div className="flex flex-1 items-center justify-center py-12">
                    <div className="w-full max-w-md rounded-3xl border border-white/80 bg-white/90 p-8 shadow-xl shadow-indigo-100">
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
                        {subtitle && <p className="mt-2 text-sm text-slate-500">{subtitle}</p>}
                        <div className="mt-8">{children}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export function TextInput({ label, error, className = '', ...props }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-slate-700">{label}</span>
            <input
                {...props}
                className={`w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${className}`}
            />
            {error && <span className="mt-1 block text-sm text-rose-600">{error}</span>}
        </label>
    );
}

export function PrimaryButton({ children, className = '', ...props }) {
    return (
        <button
            {...props}
            className={`inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60 ${className}`}
        >
            {children}
        </button>
    );
}
