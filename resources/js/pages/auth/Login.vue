<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Password from 'primevue/password';
import TextLink from '@/components/TextLink.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-medium">Email address</label>
            <InputText
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                :invalid="!!form.errors.email"
                fluid
            />
            <Message
                v-if="form.errors.email"
                severity="error"
                variant="simple"
                size="small"
            >
                {{ form.errors.email }}
            </Message>
        </div>

        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium"
                    >Password</label
                >
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-sm"
                >
                    Forgot your password?
                </TextLink>
            </div>
            <Password
                input-id="password"
                v-model="form.password"
                :feedback="false"
                toggle-mask
                required
                autocomplete="current-password"
                placeholder="Password"
                :invalid="!!form.errors.password"
                fluid
                :input-props="{ name: 'password' }"
            />
            <Message
                v-if="form.errors.password"
                severity="error"
                variant="simple"
                size="small"
            >
                {{ form.errors.password }}
            </Message>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox input-id="remember" v-model="form.remember" binary />
            <label for="remember" class="text-sm">Remember me</label>
        </div>

        <Button
            type="submit"
            label="Log in"
            class="w-full"
            :loading="form.processing"
            data-test="login-button"
        />
    </form>
</template>
