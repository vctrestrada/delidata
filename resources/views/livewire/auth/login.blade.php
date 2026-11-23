<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6" x-data="{
        entrarComo(correo) {
            const formulario = this.$refs.formulario;
            formulario.querySelector('input[name=email]').value = correo;
            formulario.querySelector('input[name=password]').value = 'password';
            formulario.requestSubmit();
        },
    }">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" x-ref="formulario">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="tu@correo.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (config('delidata.demo'))
            {{-- Accesos de demostración (para el portafolio) --}}
            <div class="rounded-xl border border-dashed border-orange-300 bg-orange-50/60 p-4">
                <div class="mb-3 flex items-center gap-2 text-sm font-medium text-orange-700">
                    <flux:icon name="sparkles" variant="micro" /> Prueba Delidata con una cuenta de demostración
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([
                        ['superadmin@delidata.test', 'Super admin', 'credit-card'],
                        ['dueno@delidata.test', 'Dueño', 'building-storefront'],
                        ['encargado@delidata.test', 'Encargado', 'clipboard-document-check'],
                        ['vendedor@delidata.test', 'Vendedor', 'shopping-cart'],
                    ] as [$correo, $rol, $icono])
                        <button type="button" x-on:click="entrarComo('{{ $correo }}')"
                            class="group flex items-center gap-2 rounded-lg border border-orange-200 bg-white px-3 py-2 text-left text-sm font-medium text-zinc-700 transition hover:-translate-y-0.5 hover:border-orange-400 hover:shadow-sm">
                            <flux:icon :name="$icono" variant="micro" class="text-orange-500 transition group-hover:scale-110" />
                            {{ $rol }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if (Route::has('register'))
        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
        @endif
    </div>
</x-layouts::auth>
