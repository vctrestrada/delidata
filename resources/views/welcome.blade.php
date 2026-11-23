<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => 'Software para restaurantes'])
    <meta name="description" content="Delidata: punto de venta, gastos y dashboard en tiempo real para restaurantes con una o varias sucursales.">
</head>
<body class="min-h-screen bg-white text-zinc-800 antialiased">
    {{-- Barra superior --}}
    <header class="sticky top-0 z-30 border-b border-zinc-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-lg bg-orange-500">
                    <x-app-logo-icon class="size-5 fill-current text-white" />
                </span>
                <span class="text-lg font-semibold tracking-tight text-zinc-900">Delidata</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm text-zinc-600 md:flex">
                <a href="#funciones" class="hover:text-orange-600">Funciones</a>
                <a href="#roles" class="hover:text-orange-600">Para quién</a>
                <a href="#planes" class="hover:text-orange-600">Planes</a>
            </nav>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">Ir a mi panel</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">Iniciar sesión</a>
            @endauth
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="absolute -right-32 -top-32 size-[28rem] rounded-full bg-orange-100 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:py-28">
                <div class="space-y-6">
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">
                        <span class="size-1.5 rounded-full bg-orange-500"></span> Ventas y gastos en tiempo real
                    </span>
                    <h1 class="text-4xl font-semibold leading-tight tracking-tight text-zinc-900 sm:text-5xl">
                        Sabe si tu restaurante <span class="text-orange-500">está ganando dinero</span>, hoy mismo.
                    </h1>
                    <p class="max-w-lg text-lg text-zinc-600">
                        Delidata junta tu punto de venta, tus gastos y un dashboard con velocímetro para que veas, en cada sucursal, cuánto vendes y cuánto te queda.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="rounded-lg bg-orange-500 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">Entrar a Delidata</a>
                        <a href="#funciones" class="rounded-lg border border-zinc-200 px-5 py-3 text-sm font-semibold text-zinc-700 hover:border-orange-300">Ver funciones</a>
                    </div>
                </div>

                {{-- Vista previa estilizada del velocímetro --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl shadow-orange-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-zinc-500">Velocímetro de gastos · últimos 30 días</div>
                            <div class="text-lg font-semibold text-zinc-900">La Esquina — Centro</div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                            <span class="size-1.5 animate-pulse rounded-full bg-green-500"></span> En vivo
                        </span>
                    </div>
                    <svg viewBox="0 0 200 110" class="mx-auto mt-6 w-full max-w-xs">
                        <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#f4f4f5" stroke-width="18" stroke-linecap="round" />
                        <path d="M 20 100 A 80 80 0 0 1 145 38" fill="none" stroke="#16a34a" stroke-width="18" stroke-linecap="round" />
                        <text x="100" y="92" text-anchor="middle" class="fill-zinc-900" style="font: 600 26px sans-serif">54%</text>
                    </svg>
                    <div class="mt-2 text-center text-sm font-semibold text-green-600">Sano</div>
                    <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-lg bg-orange-50 p-3"><div class="text-xs text-zinc-500">Ventas</div><div class="font-semibold text-zinc-900">$96,420</div></div>
                        <div class="rounded-lg bg-zinc-50 p-3"><div class="text-xs text-zinc-500">Gastos</div><div class="font-semibold text-zinc-900">$52,180</div></div>
                        <div class="rounded-lg bg-green-50 p-3"><div class="text-xs text-zinc-500">Utilidad</div><div class="font-semibold text-green-700">$44,240</div></div>
                    </div>
                    <p class="mt-4 text-center text-[11px] text-zinc-400">Cifras de ejemplo</p>
                </div>
            </div>
        </section>

        {{-- Funciones --}}
        <section id="funciones" class="border-t border-zinc-100 bg-zinc-50 py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="max-w-2xl">
                    <h2 class="text-3xl font-semibold tracking-tight text-zinc-900">Todo lo que tu restaurante necesita</h2>
                    <p class="mt-3 text-zinc-600">Cada pantalla trae un recorrido guiado: la primera vez que entras te explica paso a paso cómo usarla.</p>
                </div>
                @php
                    $funciones = [
                        ['shopping-cart', 'Punto de venta', 'Tus vendedores cobran en segundos tocando productos; calcula el cambio y genera el ticket.'],
                        ['building-storefront', 'Varias sucursales', 'Un mismo catálogo con precios especiales y disponibilidad por restaurante.'],
                        ['banknotes', 'Control de gastos', 'Insumos, renta, nómina y servicios con foto del comprobante, guardada de forma privada.'],
                        ['chart-pie', 'Dashboard con velocímetro', 'Qué porcentaje de lo que vendes se va en gastos, tus productos estrella y tus horas pico.'],
                        ['bolt', 'Tiempo real', 'Cada venta aparece al instante en el panel del dueño. Si algo se agota, desaparece del punto de venta.'],
                        ['shield-check', 'Roles y seguridad', 'Cada persona ve solo lo que le toca: dueño, encargado o vendedor.'],
                    ];
                @endphp
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($funciones as [$icono, $titulo, $texto])
                        <div class="rounded-xl border border-zinc-200 bg-white p-6">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                                <flux:icon :name="$icono" variant="outline" class="size-5" />
                            </div>
                            <h3 class="mt-4 font-semibold text-zinc-900">{{ $titulo }}</h3>
                            <p class="mt-1 text-sm text-zinc-600">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Roles --}}
        <section id="roles" class="py-20">
            <div class="mx-auto max-w-6xl px-6">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900">Una cuenta para cada quien</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['Dueño', 'Registra tus restaurantes, arma tu menú, da de alta a tu personal y revisa el dashboard de todo tu negocio.'],
                        ['Encargado', 'Administra su restaurante: vendedores, productos agotados, ventas del día y gastos.'],
                        ['Vendedor', 'Cobra desde el punto de venta y revisa su corte de caja al final del turno.'],
                    ] as $i => [$rol, $texto])
                        <div class="relative rounded-xl border border-zinc-200 p-6">
                            <span class="absolute -top-3 left-6 rounded-full bg-orange-500 px-2.5 py-0.5 text-xs font-semibold text-white">{{ $i + 1 }}</span>
                            <h3 class="font-semibold text-zinc-900">{{ $rol }}</h3>
                            <p class="mt-2 text-sm text-zinc-600">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Planes --}}
        <section id="planes" class="border-t border-zinc-100 bg-zinc-50 py-20">
            <div class="mx-auto max-w-6xl px-6">
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-900">Planes</h2>
                <p class="mt-3 text-zinc-600">Precios mensuales en pesos mexicanos. Todos los planes incluyen todas las funciones.</p>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach (\App\Enums\PlanLicencia::cases() as $plan)
                        @php($destacado = $plan === \App\Enums\PlanLicencia::Profesional)
                        <div @class([
                            'flex flex-col rounded-2xl border bg-white p-8',
                            'border-orange-400 shadow-lg shadow-orange-100' => $destacado,
                            'border-zinc-200' => ! $destacado,
                        ])>
                            @if ($destacado)
                                <span class="mb-4 self-start rounded-full bg-orange-500 px-2.5 py-0.5 text-xs font-semibold text-white">Más elegido</span>
                            @endif
                            <h3 class="text-lg font-semibold text-zinc-900">{{ $plan->etiqueta() }}</h3>
                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="text-4xl font-semibold tracking-tight text-zinc-900">${{ number_format($plan->precioMensual()) }}</span>
                                <span class="text-sm text-zinc-500">/ mes</span>
                            </div>
                            <p class="mt-4 text-sm text-zinc-600">
                                {{ $plan->maxRestaurantes() === 1 ? '1 restaurante' : 'Hasta '.$plan->maxRestaurantes().' restaurantes' }},
                                personal ilimitado y soporte por correo.
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-zinc-100 py-8">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-6 text-sm text-zinc-500 sm:flex-row">
            <span>© {{ now()->year }} Delidata</span>
            <span>Hecho con Laravel, Livewire y Reverb</span>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
