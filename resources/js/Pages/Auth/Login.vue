<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <MainLayout>

        <Head title="Đăng nhập" />

        <section class="login-register section--lg">
            <div class="login-register__container container grid"
                style="grid-template-columns: 1fr; max-width: 600px; margin-inline: auto;">
                <div class="login">
                    <h3 class="section__title" style="text-align: center;">Đăng nhập</h3>

                    <form @submit.prevent="submit" class="form grid" novalidate>
                        <div>
                            <input type="email" placeholder="Địa chỉ Email" class="form__input" v-model="form.email"
                                style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mt-4">
                            <input type="password" placeholder="Mật khẩu" class="form__input" v-model="form.password"
                                style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="mt-4 flex justify-end">
                            <Link v-if="canResetPassword" :href="route('password.request')" class="
                                    relative text-sm font-medium text-gray-600 no-underline
                                    transition-all duration-200
                                    hover:text-teal-600
                                    after:absolute after:left-0 after:-bottom-0.5
                                    after:h-[1.5px] after:w-0 after:bg-teal-600
                                    after:transition-all after:duration-200
                                    hover:after:w-full
                                ">
                                Quên mật khẩu?
                            </Link>
                        </div>
                        <div class="form__btn mt-4" style="display: flex; justify-content: flex-end;">
                            <button class="btn" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Đăng nhập
                            </button>
                        </div>

                        <div class="mt-4">
                            <p>Bạn chưa có tài khoản? <Link :href="route('register')" class="
                                    relative font-medium text-gray-600 no-underline
                                    transition-all duration-200
                                    hover:text-teal-600
                                    after:absolute after:left-0 after:-bottom-0.5
                                    after:h-[1.5px] after:w-0 after:bg-teal-600
                                    after:transition-all after:duration-200
                                    hover:after:w-full" style="color: var(--first-color);">
                                    Đăng ký ngay
                                </Link>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </MainLayout>
</template>
