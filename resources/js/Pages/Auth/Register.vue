<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <MainLayout>

        <Head title="Đăng ký" />

        <section class="login-register section--lg">
            <div class="login-register__container container grid"
                style="grid-template-columns: 1fr; max-width: 600px; margin-inline: auto;">
                <div class="register">
                    <h3 class="section__title" style="text-align: center;">Tạo tài khoản</h3>

                    <form @submit.prevent="submit" class="form grid" novalidate>
                        <div>
                            <input type="text" placeholder="Họ và tên" class="form__input" v-model="form.name" required
                                style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mt-4">
                            <input type="email" placeholder="Địa chỉ Email" class="form__input" v-model="form.email"
                                style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mt-4">
                            <input type="password" placeholder="Mật khẩu" class="form__input" v-model="form.password"
                                style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="mt-4">
                            <input type="password" placeholder="Nhập lại mật khẩu" class="form__input"
                                v-model="form.password_confirmation" style="width: 100%;" />
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>

                        <div class="form__btn mt-4"
                            style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                            <!-- Nút đăng ký -->
                            <button class="btn" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                                style="width: 50%;">
                                Đăng ký
                            </button>
                        </div>
                        <div class="mt-4 text-center">
                            <Link :href="route('login')" class="
                                    inline-flex items-center gap-1
                                    text-sm font-medium text-teal-600 no-underline
                                    transition-all duration-200 ease-out
                                    hover:text-teal-700
                                    hover:gap-2
                                ">
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
            </div>
        </section>
    </MainLayout>
</template>
