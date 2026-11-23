<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => 'Punto de venta y control para restaurantes'])
    <meta name="description" content="Delidata: punto de venta, control de gastos y dashboard en tiempo real para restaurantes con una o varias sucursales. Planes desde $499 al mes.">
</head>
<body class="min-h-screen bg-white text-zinc-800 antialiased">
    {{-- Barra superior --}}
    <header class="sticky top-0 z-40 border-b border-zinc-100/80 bg-white/85 backdrop-blur-md"
        x-data="{ abierto: false, conSombra: false }" x-on:scroll.window="conSombra = window.scrollY > 8" :class="conSombra && 'shadow-sm'">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-xl bg-orange-500 text-white shadow-md shadow-orange-200 transition group-hover:rotate-[-6deg] group-hover:scale-105">
                    <x-app-logo-icon class="size-5" />
                </span>
                <span class="text-lg font-semibold tracking-tight text-zinc-900">Delidata</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-zinc-600 md:flex">
                <a href="#funciones" class="transition hover:text-orange-600">Funciones</a>
                <a href="#como-funciona" class="transition hover:text-orange-600">Cómo funciona</a>
                <a href="#planes" class="transition hover:text-orange-600">Planes y precios</a>
                <a href="#preguntas" class="transition hover:text-orange-600">Preguntas</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-200 transition hover:-translate-y-0.5 hover:bg-orange-600">Ir a mi panel</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:text-orange-600 sm:inline-block">Iniciar sesión</a>
                    <a href="#planes" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-200 transition hover:-translate-y-0.5 hover:bg-orange-600">Ver planes</a>
                @endauth
                <button type="button" class="rounded-lg p-2 text-zinc-600 md:hidden" x-on:click="abierto = ! abierto" aria-label="Menú">
                    <flux:icon name="bars-3" />
                </button>
            </div>
        </div>
        <div x-show="abierto" x-transition x-cloak class="border-t border-zinc-100 px-6 py-4 md:hidden">
            <div class="flex flex-col gap-3 text-sm font-medium text-zinc-700" x-on:click="abierto = false">
                <a href="#funciones">Funciones</a>
                <a href="#como-funciona">Cómo funciona</a>
                <a href="#planes">Planes y precios</a>
                <a href="#preguntas">Preguntas</a>
                <a href="{{ route('login') }}" class="text-orange-600">Iniciar sesión</a>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-orange-200/60 blur-3xl animate-flotar"></div>
            <div class="pointer-events-none absolute -left-40 top-60 size-[26rem] rounded-full bg-amber-100 blur-3xl animate-flotar-lento"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-6 pb-20 pt-16 lg:grid-cols-[1.05fr_1fr] lg:pb-28 lg:pt-24">
                <div class="space-y-7">
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/80 px-3 py-1 text-xs font-semibold text-orange-700 shadow-sm animate-aparecer">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-orange-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-orange-500"></span></span>
                        Ventas y gastos en tiempo real
                    </span>
                    <h1 class="text-4xl font-semibold leading-[1.1] tracking-tight text-zinc-900 animate-aparecer [animation-delay:80ms] sm:text-6xl">
                        Sabe si tu restaurante <span class="relative whitespace-nowrap text-orange-500">está ganando<svg class="absolute -bottom-2 left-0 w-full" viewBox="0 0 300 12" fill="none"><path d="M2 9c60-6 180-8 296-3" stroke="#fdba74" stroke-width="5" stroke-linecap="round" class="trazo-animado"/></svg></span> dinero.
                    </h1>
                    <p class="max-w-xl text-lg text-zinc-600 animate-aparecer [animation-delay:160ms]">
                        Delidata junta tu punto de venta, tus gastos y un dashboard con velocímetro para que veas, sucursal por sucursal, cuánto vendes y cuánto te queda.
                    </p>
                    <div class="flex flex-wrap items-center gap-3 animate-aparecer [animation-delay:240ms]">
                        <a href="#planes" class="rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-orange-200 transition hover:-translate-y-0.5 hover:bg-orange-600">Ver planes y precios</a>
                        <a href="{{ route('login') }}" class="group inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-6 py-3.5 text-sm font-semibold text-zinc-700 transition hover:border-orange-300">
                            Probar la demo <flux:icon name="arrow-right" variant="micro" class="transition group-hover:translate-x-1" />
                        </a>
                    </div>
                    <dl class="grid max-w-md grid-cols-3 gap-6 pt-4 animate-aparecer [animation-delay:320ms]">
                        <div><dt class="text-xs text-zinc-500">Desde</dt><dd class="text-2xl font-semibold text-zinc-900" data-contador>$499</dd></div>
                        <div><dt class="text-xs text-zinc-500">Sucursales</dt><dd class="text-2xl font-semibold text-zinc-900" data-contador>20</dd></div>
                        <div><dt class="text-xs text-zinc-500">Personal</dt><dd class="text-2xl font-semibold text-zinc-900">Ilimitado</dd></div>
                    </dl>
                </div>

                {{-- Maqueta del dashboard --}}
                <div class="relative animate-aparecer [animation-delay:200ms]">
                    <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl shadow-orange-200/60">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-zinc-500">Velocímetro · últimos 30 días</div>
                                <div class="font-semibold text-zinc-900">La Esquina — Centro</div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                <span class="size-1.5 animate-pulse rounded-full bg-green-500"></span> En vivo
                            </span>
                        </div>
                        <svg viewBox="0 0 200 110" class="mx-auto mt-4 w-full max-w-xs">
                            <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#f4f4f5" stroke-width="18" stroke-linecap="round" />
                            <path d="M 20 100 A 80 80 0 0 1 145 38" fill="none" stroke="#16a34a" stroke-width="18" stroke-linecap="round" class="trazo-animado" />
                            <text x="100" y="92" text-anchor="middle" style="font: 600 26px sans-serif" fill="#18181b">54%</text>
                        </svg>
                        <div class="text-center text-sm font-semibold text-green-600">Sano</div>
                        <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl bg-orange-50 p-3"><div class="text-xs text-zinc-500">Ventas</div><div class="font-semibold text-zinc-900" data-contador>$96,420</div></div>
                            <div class="rounded-xl bg-zinc-50 p-3"><div class="text-xs text-zinc-500">Gastos</div><div class="font-semibold text-zinc-900" data-contador>$52,180</div></div>
                            <div class="rounded-xl bg-green-50 p-3"><div class="text-xs text-zinc-500">Utilidad</div><div class="font-semibold text-green-700" data-contador>$44,240</div></div>
                        </div>
                        {{-- Barras de ventas por hora --}}
                        <div class="mt-5 flex h-20 items-end gap-1.5">
                            @foreach ([18, 25, 40, 70, 95, 80, 45, 38, 55, 85, 100, 60] as $i => $alto)
                                <div class="flex-1 origin-bottom rounded-t {{ $alto >= 80 ? 'bg-orange-500' : ($alto >= 45 ? 'bg-orange-300' : 'bg-zinc-200') }} animate-aparecer" style="height: {{ $alto }}%; animation-delay: {{ 400 + $i * 50 }}ms"></div>
                            @endforeach
                        </div>
                        <p class="mt-3 text-center text-[11px] text-zinc-400">Cifras de ejemplo</p>
                    </div>

                    <div class="absolute -left-6 -top-6 hidden rounded-2xl border border-zinc-100 bg-white p-4 shadow-xl animate-flotar sm:block">
                        <div class="text-xs text-zinc-500">Nueva venta</div>
                        <div class="font-semibold text-zinc-900">#000245 · $186.00</div>
                    </div>
                    <div class="absolute -bottom-6 -right-4 hidden rounded-2xl border border-zinc-100 bg-white p-4 shadow-xl animate-flotar-lento sm:block">
                        <div class="flex items-center gap-2 text-xs text-zinc-500"><span class="size-2 rounded-full bg-red-500"></span> Agotado</div>
                        <div class="font-semibold text-zinc-900">Flan napolitano</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Funciones --}}
        <section id="funciones" class="border-y border-zinc-100 bg-zinc-50/70 py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="revelar max-w-2xl">
                    <span class="text-sm font-semibold uppercase tracking-wider text-orange-600">Funciones</span>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">Todo lo que tu restaurante necesita, en un solo lugar</h2>
                    <p class="mt-3 text-zinc-600">Y cada pantalla trae una guía: la primera vez que entras te explica paso a paso cómo usarla. Si ya la conoces, la saltas con un clic.</p>
                </div>
                @php
                    $funciones = [
                        ['shopping-cart', 'Punto de venta', 'Tus vendedores cobran en segundos tocando productos; calcula el cambio y genera el ticket.'],
                        ['building-storefront', 'Varias sucursales', 'Un mismo catálogo con precios especiales y disponibilidad por restaurante.'],
                        ['banknotes', 'Control de gastos', 'Insumos, renta, nómina y servicios con foto del comprobante, guardada de forma privada.'],
                        ['chart-pie', 'Dashboard con velocímetro', 'Qué parte de lo que vendes se va en gastos, tus productos estrella y tus horas pico.'],
                        ['bolt', 'Tiempo real', 'Cada venta aparece al instante en el panel del dueño. Si algo se agota, desaparece del punto de venta.'],
                        ['academic-cap', 'Guías paso a paso', 'Recorridos interactivos en cada pantalla para que tu equipo aprenda solo, sin capacitaciones largas.'],
                    ];
                @endphp
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($funciones as $i => [$icono, $titulo, $texto])
                        <div class="revelar tarjeta-interactiva group rounded-2xl border border-zinc-200 bg-white p-6" style="--retraso: {{ $i * 70 }}ms">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 transition group-hover:scale-110 group-hover:bg-orange-500 group-hover:text-white">
                                <flux:icon :name="$icono" variant="outline" class="size-5" />
                            </div>
                            <h3 class="mt-5 font-semibold text-zinc-900">{{ $titulo }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Cómo funciona --}}
        <section id="como-funciona" class="py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="revelar max-w-2xl">
                    <span class="text-sm font-semibold uppercase tracking-wider text-orange-600">Cómo funciona</span>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">Una cuenta para cada quien</h2>
                </div>
                <div class="relative mt-14 grid gap-6 md:grid-cols-3">
                    <div class="absolute left-0 right-0 top-6 hidden h-px bg-gradient-to-r from-orange-200 via-orange-300 to-orange-200 md:block"></div>
                    @foreach ([
                        ['Dueño', 'building-office-2', 'Registra tus restaurantes, arma tu menú con precios por sucursal, da de alta a tu personal y revisa el dashboard de todo tu negocio.'],
                        ['Encargado', 'clipboard-document-check', 'Administra su restaurante: vendedores, productos agotados, ventas del día, cancelaciones y gastos.'],
                        ['Vendedor', 'shopping-cart', 'Cobra desde el punto de venta y revisa su corte de caja al final del turno.'],
                    ] as $i => [$rol, $icono, $texto])
                        <div class="revelar relative" style="--retraso: {{ $i * 120 }}ms">
                            <div class="relative z-10 flex size-12 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-200">
                                <flux:icon :name="$icono" variant="outline" class="size-6" />
                            </div>
                            <h3 class="mt-5 text-lg font-semibold text-zinc-900">{{ $i + 1 }}. {{ $rol }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Planes --}}
        <section id="planes" class="relative overflow-hidden border-y border-zinc-100 bg-gradient-to-b from-orange-50/70 to-white py-24" x-data="{ anual: false }">
            <div class="mx-auto max-w-6xl px-6">
                <div class="revelar mx-auto max-w-2xl text-center">
                    <span class="text-sm font-semibold uppercase tracking-wider text-orange-600">Planes y precios</span>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl">Paga según el tamaño de tu negocio</h2>
                    <p class="mt-3 text-zinc-600">Todos los planes incluyen todas las funciones. Solo cambia cuántos restaurantes puedes registrar y el tipo de soporte. Precios en pesos mexicanos, IVA incluido.</p>

                    {{-- Selector mensual / anual --}}
                    <div class="mt-8 inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white p-1 text-sm font-semibold shadow-sm">
                        <button type="button" x-on:click="anual = false" class="rounded-full px-5 py-2 transition" :class="! anual ? 'bg-orange-500 text-white shadow' : 'text-zinc-600 hover:text-zinc-900'">Mensual</button>
                        <button type="button" x-on:click="anual = true" class="rounded-full px-5 py-2 transition" :class="anual ? 'bg-orange-500 text-white shadow' : 'text-zinc-600 hover:text-zinc-900'">
                            Anual <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] text-green-700">2 meses gratis</span>
                        </button>
                    </div>
                </div>

                <div class="mt-14 grid items-start gap-6 lg:grid-cols-3">
                    @foreach (\App\Enums\PlanLicencia::cases() as $i => $plan)
                        @php $destacado = $plan === \App\Enums\PlanLicencia::Profesional; @endphp
                        <div @class([
                            'revelar tarjeta-interactiva relative flex flex-col rounded-3xl border bg-white p-8',
                            'border-orange-400 shadow-2xl shadow-orange-200/70 lg:-mt-4 lg:pb-12' => $destacado,
                            'border-zinc-200' => ! $destacado,
                        ]) style="--retraso: {{ $i * 100 }}ms">
                            @if ($destacado)
                                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold text-white shadow-md animate-latido">Más elegido</span>
                            @endif

                            <h3 class="text-lg font-semibold text-zinc-900">{{ $plan->etiqueta() }}</h3>
                            <p class="text-sm text-zinc-500">{{ $plan->lema() }}</p>

                            <div class="mt-6 flex items-baseline gap-1">
                                <span class="text-5xl font-semibold tracking-tight text-zinc-900" x-show="! anual">${{ number_format($plan->precioMensual()) }}</span>
                                <span class="text-5xl font-semibold tracking-tight text-zinc-900" x-show="anual" x-cloak>${{ number_format($plan->precioAnual()) }}</span>
                                <span class="text-sm text-zinc-500" x-text="anual ? '/ año' : '/ mes'">/ mes</span>
                            </div>
                            <p class="mt-1 h-5 text-xs text-green-700" x-show="anual" x-cloak>
                                Equivale a ${{ number_format($plan->precioAnual() / 12, 2) }} al mes · ahorras ${{ number_format($plan->precioMensual() * 2) }}
                            </p>

                            <a href="mailto:{{ config('delidata.contacto') }}?subject={{ rawurlencode('Quiero el plan '.$plan->etiqueta().' de Delidata') }}"
                               @class([
                                   'mt-6 rounded-xl px-5 py-3 text-center text-sm font-semibold transition hover:-translate-y-0.5',
                                   'bg-orange-500 text-white shadow-lg shadow-orange-200 hover:bg-orange-600' => $destacado,
                                   'border border-zinc-200 text-zinc-800 hover:border-orange-300' => ! $destacado,
                               ])>
                                Contratar {{ $plan->etiqueta() }}
                            </a>

                            <ul class="mt-8 space-y-3 text-sm">
                                @foreach ($plan->beneficios() as $beneficio)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                            <flux:icon name="check" variant="micro" class="size-3.5" />
                                        </span>
                                        <span class="text-zinc-700">{{ $beneficio }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                {{-- Comparativa --}}
                <div class="revelar mt-16 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 text-left text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Comparar planes</th>
                                @foreach (\App\Enums\PlanLicencia::cases() as $plan)
                                    <th class="px-5 py-3 text-center font-semibold text-zinc-900">{{ $plan->etiqueta() }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @php
                                $filas = [
                                    ['Restaurantes', fn ($p) => (string) $p->maxRestaurantes()],
                                    ['Precio mensual', fn ($p) => '$'.number_format($p->precioMensual())],
                                    ['Precio anual', fn ($p) => '$'.number_format($p->precioAnual())],
                                    ['Punto de venta, gastos y dashboard', fn ($p) => true],
                                    ['Tiempo real', fn ($p) => true],
                                    ['Comparación entre sucursales', fn ($p) => $p !== \App\Enums\PlanLicencia::Basico],
                                    ['Soporte por WhatsApp', fn ($p) => $p !== \App\Enums\PlanLicencia::Basico],
                                    ['Capacitación para tu equipo', fn ($p) => $p === \App\Enums\PlanLicencia::Empresarial],
                                ];
                            @endphp
                            @foreach ($filas as [$concepto, $valor])
                                <tr class="transition hover:bg-orange-50/40">
                                    <td class="px-5 py-3 text-zinc-700">{{ $concepto }}</td>
                                    @foreach (\App\Enums\PlanLicencia::cases() as $plan)
                                        @php $v = $valor($plan); @endphp
                                        <td class="px-5 py-3 text-center">
                                            @if ($v === true)
                                                <flux:icon name="check-circle" variant="solid" class="mx-auto size-5 text-orange-500" />
                                            @elseif ($v === false)
                                                <span class="text-zinc-300">—</span>
                                            @else
                                                <span class="font-medium text-zinc-900">{{ $v }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Preguntas frecuentes --}}
        <section id="preguntas" class="py-24">
            <div class="mx-auto grid max-w-6xl gap-12 px-6 lg:grid-cols-[1fr_1.4fr]">
                <div class="revelar">
                    <span class="text-sm font-semibold uppercase tracking-wider text-orange-600">Preguntas frecuentes</span>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900">¿Tienes dudas?</h2>
                    <p class="mt-3 text-zinc-600">Escríbenos a <a href="mailto:{{ config('delidata.contacto') }}" class="font-medium text-orange-600 hover:underline">{{ config('delidata.contacto') }}</a> y te respondemos el mismo día.</p>
                </div>
                <div class="space-y-3">
                    @foreach ([
                        ['¿Necesito instalar algo?', 'No. Delidata funciona desde el navegador en computadora, tablet o celular. Para imprimir tickets basta una impresora térmica de 80 mm.'],
                        ['¿Puedo cambiar de plan después?', 'Sí. Si abres otra sucursal te pasamos a un plan mayor y tus datos se conservan.'],
                        ['¿Qué pasa si se vence mi suscripción?', 'Tu información no se borra. El acceso se pausa hasta que renueves y todo queda como lo dejaste.'],
                        ['¿Mis vendedores pueden ver mis ganancias?', 'No. Cada persona ve solo lo que le corresponde: el vendedor su punto de venta, el encargado su restaurante y tú todo el negocio.'],
                        ['¿Cómo aprende mi equipo a usarlo?', 'Cada pantalla tiene una guía paso a paso que aparece la primera vez. Se puede saltar o repetir cuando quieran.'],
                    ] as $i => [$pregunta, $respuesta])
                        <details class="revelar group rounded-2xl border border-zinc-200 bg-white p-5 transition open:border-orange-300 open:shadow-md open:shadow-orange-100" style="--retraso: {{ $i * 60 }}ms">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-zinc-900">
                                {{ $pregunta }}
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-50 text-orange-600 transition group-open:rotate-45">
                                    <flux:icon name="plus" variant="micro" />
                                </span>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $respuesta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Llamado final --}}
        <section class="px-6 pb-24">
            <div class="revelar relative mx-auto max-w-6xl overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 to-amber-500 px-8 py-16 text-center text-white shadow-2xl shadow-orange-200">
                <div class="pointer-events-none absolute -right-20 -top-20 size-72 rounded-full bg-white/10 blur-2xl animate-flotar"></div>
                <h2 class="relative text-3xl font-semibold tracking-tight sm:text-4xl">Empieza a ver tus números claros hoy</h2>
                <p class="relative mx-auto mt-3 max-w-xl text-orange-50">Entra a la demo con datos de ejemplo y recorre todo el sistema como dueño, encargado o vendedor.</p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('login') }}" class="rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-orange-600 shadow-lg transition hover:-translate-y-0.5">Probar la demo</a>
                    <a href="#planes" class="rounded-xl border border-white/40 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">Ver planes</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-zinc-100 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 text-sm text-zinc-500 sm:flex-row">
            <div class="flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-lg bg-orange-500 text-white"><x-app-logo-icon class="size-4" /></span>
                <span class="font-medium text-zinc-700">Delidata</span>
                <span>© {{ now()->year }}</span>
            </div>
            <div class="flex gap-6">
                <a href="#planes" class="hover:text-orange-600">Planes</a>
                <a href="#preguntas" class="hover:text-orange-600">Preguntas</a>
                <a href="mailto:{{ config('delidata.contacto') }}" class="hover:text-orange-600">Contacto</a>
            </div>
        </div>
    </footer>

    @livewireScripts
    @fluxScripts
</body>
</html>
