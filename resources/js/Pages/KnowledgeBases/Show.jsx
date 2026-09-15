import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import AuthenticatedLayout, { StatusBadge } from '@/Layouts/AuthenticatedLayout';

export default function Show({ knowledgeBase, embedSnippet, appUrl }) {
    const upload = useForm({ file: null });
    const hasActiveJobs = knowledgeBase.documents.some((doc) => ['pending', 'processing'].includes(doc.status));

    useEffect(() => {
        if (!hasActiveJobs) {
            return undefined;
        }

        const timer = setInterval(() => {
            router.reload({ only: ['knowledgeBase', 'flash'] });
        }, 4000);

        return () => clearInterval(timer);
    }, [hasActiveJobs]);

    return (
        <AuthenticatedLayout
            title={knowledgeBase.name}
            actions={
                <Link
                    href={`/knowledge-bases/${knowledgeBase.id}/edit`}
                    className="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Embed & settings
                </Link>
            }
        >
            <Head title={knowledgeBase.name} />
            <div className="grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-900">Documents</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Upload PDF, Markdown, TXT, or DOCX. Processing runs on Redis via Horizon.
                    </p>
                    <form
                        className="mt-5 flex flex-col gap-3 sm:flex-row"
                        onSubmit={(event) => {
                            event.preventDefault();
                            upload.post(`/knowledge-bases/${knowledgeBase.id}/documents`, {
                                forceFormData: true,
                                onSuccess: () => upload.reset('file'),
                            });
                        }}
                    >
                        <input
                            type="file"
                            accept=".pdf,.md,.txt,.docx"
                            onChange={(event) => upload.setData('file', event.target.files[0])}
                            className="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700"
                        />
                        <button
                            type="submit"
                            disabled={upload.processing || !upload.data.file}
                            className="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Upload
                        </button>
                    </form>
                    {upload.errors.file && <p className="mt-2 text-sm text-rose-600">{upload.errors.file}</p>}

                    <ul className="mt-6 divide-y divide-slate-100">
                        {knowledgeBase.documents.map((document) => (
                            <li key={document.id} className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="font-medium text-slate-900">{document.original_filename}</p>
                                    <p className="text-xs text-slate-500">
                                        {document.chunk_count} chunks
                                        {document.error_message ? ` · ${document.error_message}` : ''}
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <StatusBadge status={document.status} />
                                    {document.status === 'failed' && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    `/knowledge-bases/${knowledgeBase.id}/documents/${document.id}/reprocess`,
                                                )
                                            }
                                            className="text-sm font-medium text-indigo-600"
                                        >
                                            Retry
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/knowledge-bases/${knowledgeBase.id}/documents/${document.id}`,
                                            )
                                        }
                                        className="text-sm font-medium text-rose-600"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>
                    {knowledgeBase.documents.length === 0 && (
                        <p className="mt-6 text-sm text-slate-500">No documents yet.</p>
                    )}
                </section>
                <aside className="space-y-6">
                    <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-slate-900">Embed code</h2>
                        <p className="mt-1 text-sm text-slate-500">Paste this snippet before the closing body tag.</p>
                        <pre className="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">
                            {embedSnippet}
                        </pre>
                        <button
                            type="button"
                            onClick={() => navigator.clipboard.writeText(embedSnippet)}
                            className="mt-3 text-sm font-medium text-indigo-600"
                        >
                            Copy snippet
                        </button>
                    </section>
                    <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-slate-900">Live preview</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            The widget loads against {appUrl}. Ask a question after a document is marked ready.
                        </p>
                        <WidgetPreview snippet={embedSnippet} />
                    </section>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function WidgetPreview({ snippet }) {
    useEffect(() => {
        const match = snippet.match(/data-kb-id="([^"]+)"/);
        const srcMatch = snippet.match(/src="([^"]+)"/);
        if (!match || !srcMatch || document.getElementById('docagent-preview-script')) {
            return undefined;
        }

        const script = document.createElement('script');
        script.id = 'docagent-preview-script';
        script.src = `${srcMatch[1]}?preview=${Date.now()}`;
        script.async = true;
        script.setAttribute('data-kb-id', match[1]);
        document.body.appendChild(script);

        return () => {
            script.remove();
            document.getElementById('docagent-widget-host')?.remove();
        };
    }, [snippet]);

    return <p className="mt-4 text-xs text-slate-400">The chat bubble appears in the bottom-right corner.</p>;
}
