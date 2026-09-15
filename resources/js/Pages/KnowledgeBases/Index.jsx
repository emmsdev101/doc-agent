import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ knowledgeBases }) {
    return (
        <AuthenticatedLayout
            title="Knowledge bases"
            actions={
                <Link
                    href="/knowledge-bases/create"
                    className="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    New knowledge base
                </Link>
            }
        >
            <Head title="Knowledge bases" />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {knowledgeBases.map((kb) => (
                    <Link
                        key={kb.id}
                        href={`/knowledge-bases/${kb.id}`}
                        className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md"
                    >
                        <h2 className="text-lg font-semibold text-slate-900">{kb.name}</h2>
                        <p className="mt-2 line-clamp-2 text-sm text-slate-500">
                            {kb.description || 'No description yet.'}
                        </p>
                        <p className="mt-4 text-xs font-medium uppercase tracking-wide text-indigo-600">
                            {kb.documents_count} documents
                        </p>
                    </Link>
                ))}
            </div>
            {knowledgeBases.length === 0 && (
                <div className="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p className="text-slate-600">Create a knowledge base to upload documents and generate an embed snippet.</p>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
