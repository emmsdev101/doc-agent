import { Head, Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="AI knowledge base for your website" />
            <div className="min-h-screen bg-slate-950 text-white">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-2 text-lg font-bold">
                        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500">D</span>
                        DocAgent
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href="/login" className="text-sm font-medium text-slate-300 hover:text-white">
                            Sign in
                        </Link>
                        <Link
                            href="/register"
                            className="rounded-full bg-indigo-500 px-4 py-2 text-sm font-semibold hover:bg-indigo-400"
                        >
                            Start free
                        </Link>
                    </div>
                </header>
                <main className="mx-auto max-w-6xl px-6 pb-24 pt-16">
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">B2B knowledge base SaaS</p>
                    <h1 className="mt-4 max-w-3xl text-5xl font-extrabold tracking-tight sm:text-6xl">
                        Turn your docs into an embeddable AI assistant.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-slate-300">
                        Upload PDFs, Markdown, and FAQs. DocAgent chunks them, stores embeddings in PostgreSQL with pgvector, and answers website visitors from your own content using retrieval-augmented generation.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-4">
                        <Link
                            href="/register"
                            className="rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 hover:bg-slate-200"
                        >
                            Create a knowledge base
                        </Link>
                        <Link
                            href="/login"
                            className="rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            Open dashboard
                        </Link>
                    </div>
                    <div className="mt-16 grid gap-6 md:grid-cols-3">
                        {[
                            ['Upload once', 'PDF, Markdown, TXT, and DOCX files are parsed, chunked, and embedded asynchronously on Redis + Horizon.'],
                            ['Retrieve precisely', 'Every widget question is embedded and matched with cosine similarity against your private vector index.'],
                            ['Embed anywhere', 'Drop a single script tag on any site. The chat bubble talks to CORS-secured widget APIs.'],
                        ].map(([title, copy]) => (
                            <div key={title} className="rounded-3xl border border-white/10 bg-white/5 p-6">
                                <h2 className="text-lg font-semibold">{title}</h2>
                                <p className="mt-3 text-sm leading-6 text-slate-300">{copy}</p>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
