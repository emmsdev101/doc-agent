import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout, { PrimaryButton, TextInput } from '@/Layouts/GuestLayout';

export default function Register() {
    const form = useForm({
        name: '',
        organization: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <GuestLayout title="Create your workspace" subtitle="Spin up a knowledge base for your team in minutes.">
            <Head title="Register" />
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/register');
                }}
            >
                <TextInput
                    label="Your name"
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                    error={form.errors.name}
                    required
                />
                <TextInput
                    label="Organization"
                    value={form.data.organization}
                    onChange={(event) => form.setData('organization', event.target.value)}
                    error={form.errors.organization}
                    required
                />
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
                <TextInput
                    label="Confirm password"
                    type="password"
                    value={form.data.password_confirmation}
                    onChange={(event) => form.setData('password_confirmation', event.target.value)}
                    required
                />
                <PrimaryButton disabled={form.processing}>Create workspace</PrimaryButton>
            </form>
            <p className="mt-6 text-center text-sm text-slate-500">
                Already have an account?{' '}
                <Link href="/login" className="font-semibold text-indigo-600">
                    Sign in
                </Link>
            </p>
        </GuestLayout>
    );
}
