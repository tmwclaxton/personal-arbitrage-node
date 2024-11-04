<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
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
    <div class="mb-4 text-sm text-gray-600 w-96 mx-auto my-auto  h-screen flex flex-col py-auto justify-items-center">
        <div class="my-auto">
            <Head title="Log in"/>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>
			<a href="#" class="flex items-center justify-center text-2xl font-semibold text-gray-900 dark:text-white">
				<img src="/images/logoLight.png" class="h-24 block dark:hidden" alt="Lightning Arbitrage Solutions">
				<img src="/images/logoDark.png" class="h-24 hidden dark:block" alt="Lightning Arbitrage Solutions">
			</a>
			<p class="text-center font-semibold text-gray-900 dark:text-white mb-5">
				Support
                <span class="mt-1">Number: +447837370669</span>
                <br>
                <span class="mt-1">Telegram: @las_2024</span>
                <br>
                <span class="mt-1">Signal: @las.24</span>
			</p>

            <form @submit.prevent="submit">
                <!--<div>-->
                <!--    <InputLabel for="username" value="Username"/>-->

                <!--    <TextInput-->
                <!--        id="email"-->
                <!--        type="email"-->
                <!--        class="mt-1 block w-full"-->
                <!--        v-model="form.email"-->
                <!--        required-->
                <!--        autofocus-->
                <!--        autocomplete="username"-->
                <!--    />-->

                <!--    <InputError class="mt-2" :message="form.errors.email"/>-->
                <!--</div>-->

                <div class="mt-4">
                    <InputLabel for="password" value="Passcode"/>

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
						:confidential="true"
                    />

                    <InputError class="mt-2" :message="form.errors.password"/>
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember"/>
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">
							Remember me
						</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <!--<Link-->
                    <!--    v-if="canResetPassword"-->
                    <!--    :href="route('password.request')"-->
                    <!--    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"-->
                    <!--&gt;-->
                    <!--    Forgot your password?-->
                    <!--</Link>-->

                    <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>
