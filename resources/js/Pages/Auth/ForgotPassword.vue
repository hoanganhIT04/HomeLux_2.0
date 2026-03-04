<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <GuestLayout>

        <Head title="Quên mật khẩu" />

        <div class="min-h-screen flex items-center justify-center bg-[#eaf7f2]">
            <div class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-teal-600 text-white text-center py-6">
                    <Link :href="route('home')" class="no-underline">
                        <h1 class="text-2xl font-bold tracking-wide hover:opacity-80 transition">
                            Home Lux 2.0
                        </h1>
                    </Link>
                    <p class="mt-1 text-sm opacity-90">Khôi phục mật khẩu</p>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4 text-center">
                        Nhập email đã đăng ký. Chúng tôi sẽ gửi cho bạn liên kết để đặt lại mật khẩu.
                    </p>

                    <div v-if="status"
                        class="mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-md px-3 py-2 text-center">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="email" value="Địa chỉ Email" />

                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                                autofocus autocomplete="username" />

                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mt-6">
                            <PrimaryButton class="w-full justify-center bg-teal-600 hover:bg-teal-700"
                                :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                                Gửi liên kết đặt lại mật khẩu
                            </PrimaryButton>
                        </div>
                        <div class="mt-4 text-center">
                            <Link
                                :href="route('login')"
                                class="
                                    inline-flex items-center gap-1
                                    text-sm font-medium text-teal-600 no-underline
                                    transition-all duration-200 ease-out
                                    hover:text-teal-700
                                    hover:gap-2
                                "
                            >
                                <span class="transition-transform duration-200 hover:-translate-x-1">
                                    ←
                                </span>
                                <span class="relative after:absolute after:left-0 after:-bottom-0.5
                                            after:h-[1.5px] after:w-0 after:bg-teal-600
                                            after:transition-all after:duration-200
                                            hover:after:w-full">
                                    Quay lại đăng nhập
                                </span>
                            </Link>
                        </div>

                    </form>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 text-center text-xs text-gray-500 py-3">
                    © 2026 Home Lux 2.0
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
