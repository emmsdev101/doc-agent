import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout, { PrimaryButton, TextInput } from '@/Layouts/GuestLayout';

export default function Login() {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    return (
        <GuestLayout title="Welcome back" subtitle="Sign in to manage your knowledge bases.">
            <Head title="Sign in" />
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/login');
                }}
            >
                <TextInput
                    label="Work email"
                    type="email"
                    value={form.data.email}
                    onChange={(event) => form.setData('email', event.target.value)}
                    error={form.errors.email}
                    required
                />
                <TextInput
                    label="Password"
                    type="password"
                    value={form.data.password}
                    onChange={(event) => form.setData('password', event.target.value)}
                    error={form.errors.password}
                    required
                />
                <label className="flex items-center gap-2 text-sm text-slate-600">
                    <input
                        type="checkbox"
                        checked={form.data.remember}
                        onChange={(event) => form.setData('remember', event.target.checked)}
                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    Remember me
                </label>
                <PrimaryButton disabled={form.processing}>Sign in</PrimaryButton>
            </form>
            <p className="mt-6 text-center text-sm text-slate-500">
                New to DocAgent?{' '}
                <Link href="/register" className="font-semibold text-indigo-600">
                    Create an organization
                </Link>
            </p>
        </GuestLayout>
    );
}
