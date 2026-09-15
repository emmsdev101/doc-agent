import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Dashboard({ organization, knowledgeBases, stats }) {
    return (
        <AuthenticatedLayout
            title="Overview"
            actions={
                <Link
                    href="/knowledge-bases/create"
                    className="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    New knowledge base
                </Link>
            }
        >
            <Head title="Dashboard" />
            <div className="grid gap-4 md:grid-cols-3">
                <Stat label="Knowledge bases" value={stats.knowledge_bases} />
                <Stat label="Ready documents" value={stats.ready_documents} />
                <Stat label="Processing" value={stats.pending_documents} />
            </div>
            <section className="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-slate-900">Recent knowledge bases</h2>
                    <Link href="/knowledge-bases" className="text-sm font-medium text-indigo-600">
                        View all
                    </Link>
                </div>
                {knowledgeBases.length === 0 ? (
                    <p className="mt-6 text-sm text-slate-500">
                        {organization?.name} does not have a knowledge base yet. Create one to start ingesting documents.
                    </p>
                ) : (
                    <ul className="mt-6 divide-y divide-slate-100">
                        {knowledgeBases.map((kb) => (
                            <li key={kb.id} className="flex items-center justify-between py-3">
                                <div>
                                    <Link href={`/knowledge-bases/${kb.id}`} className="font-medium text-slate-900 hover:text-indigo-600">
                                        {kb.name}
                                    </Link>
                                    <p className="text-sm text-slate-500">{kb.documents_count} documents</p>
                                </div>
                                <Link href={`/knowledge-bases/${kb.id}/edit`} className="text-sm text-slate-500 hover:text-slate-900">
                                    Embed snippet
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AuthenticatedLayout>
    );
}

function Stat({ label, value }) {
    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-3xl font-bold text-slate-900">{value}</p>
        </div>
    );
}
