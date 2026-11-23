<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php
                $usuario = auth()->user();
                $rol = $usuario->rolPrincipal();
                $contexto = match ($rol) {
                    \App\Enums\Rol::Dueno => $usuario->negocio?->nombre,
                    \App\Enums\Rol::Encargado, \App\Enums\Rol::Vendedor => $usuario->restaurante?->nombre,
                    default => 'Plataforma Delidata',
                };
                $menu = match ($rol) {
                    \App\Enums\Rol::SuperAdmin => [
                        ['Inicio', 'home', 'admin.inicio', 'admin.inicio', null],
                        ['Suscripciones', 'credit-card', 'admin.suscripciones.index', 'admin.suscripciones.*', 'menu-suscripciones'],
                    ],
                    \App\Enums\Rol::Dueno => [
                        ['Inicio', 'home', 'negocio.inicio', 'negocio.inicio', null],
                        ['Restaurantes', 'building-storefront', 'negocio.restaurantes.index', 'negocio.restaurantes.*', 'menu-restaurantes'],
                        ['Productos', 'book-open', 'negocio.productos.index', 'negocio.productos.*', 'menu-productos'],
                        ['Personal', 'user-group', 'negocio.personal.index', 'negocio.personal.*', 'menu-personal'],
                    ],
                    \App\Enums\Rol::Encargado => [
                        ['Inicio', 'home', 'encargado.inicio', 'encargado.inicio', null],
                        ['Menú', 'book-open', 'encargado.menu', 'encargado.menu', 'menu-menu'],
                        ['Vendedores', 'user-group', 'encargado.personal.index', 'encargado.personal.*', 'menu-personal'],
                    ],
                    \App\Enums\Rol::Vendedor => [
                        ['Inicio', 'home', 'ventas.inicio', 'ventas.*', null],
                    ],
                    default => [
                        ['Inicio', 'home', 'dashboard', 'dashboard', null],
                    ],
                };
            @endphp

            @if ($contexto)
                <div class="mx-2 mb-1 rounded-lg border border-orange-100 bg-orange-50 px-3 py-2">
                    <div class="text-[11px] font-medium uppercase tracking-wider text-orange-600">{{ $rol?->etiqueta() ?? 'Sin rol' }}</div>
                    <div class="truncate text-sm font-medium text-zinc-900">{{ $contexto }}</div>
                </div>
            @endif

            <flux:sidebar.nav>
                @foreach ($menu as [$texto, $icono, $ruta, $patron, $tour])
                    <flux:sidebar.item
                        :icon="$icono"
                        :href="route($ruta)"
                        :current="request()->routeIs($patron)"
                        wire:navigate
                        :data-tour="$tour"
                    >
                        {{ $texto }}
                    </flux:sidebar.item>
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Configuración
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Cerrar sesión
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
