import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PrimaryButton, TextInput } from '@/Layouts/GuestLayout';

export default function Settings({ knowledgeBase, embedSnippet, allowedOriginsText }) {
    const form = useForm({
        name: knowledgeBase.name,
        description: knowledgeBase.description ?? '',
        welcome_message: knowledgeBase.welcome_message,
        system_prompt: knowledgeBase.system_prompt ?? '',
        allowed_origins: allowedOriginsText,
        primary_color: knowledgeBase.primary_color,
        is_active: knowledgeBase.is_active,
        rotate_token: false,
    });

    return (
        <AuthenticatedLayout title={`${knowledgeBase.name} settings`}>
            <Head title="Settings" />
            <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <form
                    className="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.patch(`/knowledge-bases/${knowledgeBase.id}`);
                    }}
                >
                    <TextInput
                        label="Name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        error={form.errors.name}
                    />
                    <label className="block">
                        <span className="mb-1.5 block text-sm font-medium text-slate-700">Description</span>
                        <textarea
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            className="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={3}
                        />
                    </label>
                    <TextInput
                        label="Welcome message"
                        value={form.data.welcome_message}
                        onChange={(event) => form.setData('welcome_message', event.target.value)}
                    />
                    <label className="block">
                        <span className="mb-1.5 block text-sm font-medium text-slate-700">System prompt override</span>
                        <textarea
                            value={form.data.system_prompt}
                            onChange={(event) => form.setData('system_prompt', event.target.value)}
                            className="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={5}
                            placeholder="Leave blank to use the default RAG instructions."
                        />
                    </label>
                    <label className="block">
                        <span className="mb-1.5 block text-sm font-medium text-slate-700">Allowed origins</span>
                        <textarea
                            value={form.data.allowed_origins}
                            onChange={(event) => form.setData('allowed_origins', event.target.value)}
                            className="w-full rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                        />
                    </label>
                    <TextInput
                        label="Widget color"
                        type="color"
                        value={form.data.primary_color}
                        onChange={(event) => form.setData('primary_color', event.target.value)}
                        className="h-11 w-24 p-1"
                    />
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) => form.setData('is_active', event.target.checked)}
                            className="rounded border-slate-300 text-indigo-600"
                        />
                        Widget is active
                    </label>
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={form.data.rotate_token}
                            onChange={(event) => form.setData('rotate_token', event.target.checked)}
                            className="rounded border-slate-300 text-indigo-600"
                        />
                        Rotate widget token (invalidates existing snippets)
                    </label>
                    <PrimaryButton className="w-auto px-6" disabled={form.processing}>
                        Save settings
                    </PrimaryButton>
                </form>
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-slate-900">Embed snippet</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Widget UUID: <span className="font-mono text-xs">{knowledgeBase.widget_token}</span>
                    </p>
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
                    <button
                        type="button"
                        onClick={() => {
                            if (confirm('Delete this knowledge base and all of its documents?')) {
                                router.delete(`/knowledge-bases/${knowledgeBase.id}`);
                            }
                        }}
                        className="mt-8 block text-sm font-medium text-rose-600"
                    >
                        Delete knowledge base
                    </button>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
