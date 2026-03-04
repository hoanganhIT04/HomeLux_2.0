<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';


const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Đặt lại mật khẩu" />

        <div class="min-h-screen flex items-center justify-center bg-[#e9f8ec] px-4">
            <div class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden border border-[#cce7d0]">
                <!-- Header -->
                <div class="bg-[#088178] py-6 text-center">
                    <Link :href="route('home')" class="no-underline">
                        <h1 class="text-2xl font-bold tracking-wide hover:opacity-80 transition">
                            Home Lux 2.0
                        </h1>
                    </Link>
                    <p class="text-white/90 mt-1">
                        Đặt lại mật khẩu
                    </p>
                </div>

                <!-- Body -->
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div class="text-sm text-gray-600">
                        Vui lòng nhập mật khẩu mới cho tài khoản của bạn.
                    </div>

                    <!-- Email (readonly) -->
                    <div>
                        <InputLabel for="email" value="Email" />

                        <TextInput id="email" type="email" class="mt-1 block w-full bg-gray-100 cursor-not-allowed"
                            v-model="form.email" readonly disabled />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel for="password" value="Mật khẩu mới" />

                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password"
                            required autocomplete="new-password" />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Confirm -->
                    <div>
                        <InputLabel for="password_confirmation" value="Xác nhận mật khẩu" />

                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full"
                            v-model="form.password_confirmation" required autocomplete="new-password" />

                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Button -->
                    <div class="pt-4">
                        <PrimaryButton class="w-full justify-center bg-[#088178] hover:bg-[#066f67]"
                            :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            ĐẶT LẠI MẬT KHẨU
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Footer -->
                <div class="bg-[#f6fffa] text-center py-4 text-xs text-gray-500">
                    © {{ new Date().getFullYear() }} Home Lux 2.0
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
