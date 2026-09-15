import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PrimaryButton, TextInput } from '@/Layouts/GuestLayout';

export default function Create() {
    const form = useForm({
        name: '',
        description: '',
        welcome_message: 'Hi! Ask me anything about our docs.',
        allowed_origins: '*',
        primary_color: '#4f46e5',
    });

    return (
        <AuthenticatedLayout title="New knowledge base">
            <Head title="New knowledge base" />
            <form
                className="max-w-2xl space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/knowledge-bases');
                }}
            >
                <TextInput
                    label="Name"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                    error={form.errors.name}
                    required
                />
                <label className="block">
                    <span className="mb-1.5 block text-sm font-medium text-slate-700">Description</span>
                    <textarea
                        value={form.data.description}
                        onChange={(event) => form.setData('description', event.target.value)}
                        className="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={4}
                    />
                </label>
                <TextInput
                    label="Widget welcome message"
                    value={form.data.welcome_message}
                    onChange={(event) => form.setData('welcome_message', event.target.value)}
                />
                <label className="block">
                    <span className="mb-1.5 block text-sm font-medium text-slate-700">Allowed origins</span>
                    <textarea
                        value={form.data.allowed_origins}
                        onChange={(event) => form.setData('allowed_origins', event.target.value)}
                        className="w-full rounded-xl border-slate-200 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={3}
                    />
                    <span className="mt-1 block text-xs text-slate-500">
                        One origin per line, or `*` to allow any website.
                    </span>
                </label>
                <TextInput
                    label="Widget color"
                    type="color"
                    value={form.data.primary_color}
                    onChange={(event) => form.setData('primary_color', event.target.value)}
                    className="h-11 w-24 p-1"
                />
                <PrimaryButton className="w-auto px-6" disabled={form.processing}>
                    Create knowledge base
                </PrimaryButton>
            </form>
        </AuthenticatedLayout>
    );
}
