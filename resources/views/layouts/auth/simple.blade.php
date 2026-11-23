<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="grid min-h-svh lg:grid-cols-[1.05fr_1fr]">
            {{-- Panel de marca --}}
            <aside class="relative hidden overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 p-12 text-white lg:flex lg:flex-col">
                <div class="pointer-events-none absolute -left-24 -top-24 size-96 rounded-full bg-white/10 blur-2xl animate-flotar-lento"></div>
                <div class="pointer-events-none absolute -bottom-32 -right-16 size-[28rem] rounded-full bg-amber-300/30 blur-3xl animate-flotar"></div>

                <a href="{{ route('home') }}" class="relative flex items-center gap-3" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-xl bg-white text-orange-500 shadow-lg">
                        <x-app-logo-icon class="size-7" />
                    </span>
                    <span class="text-2xl font-semibold tracking-tight">Delidata</span>
                </a>

                <div class="relative my-auto max-w-md space-y-8">
                    <h2 class="text-4xl font-semibold leading-tight tracking-tight animate-aparecer">
                        Tu restaurante, en números claros.
                    </h2>
                    <p class="text-lg text-orange-50/90 animate-aparecer [animation-delay:120ms]">
                        Cobra, controla tus gastos y mira en tiempo real cuánto te queda en cada sucursal.
                    </p>

                    {{-- Tarjetas flotantes ilustrativas --}}
                    <div class="relative h-56">
                        <div class="absolute left-0 top-0 w-64 rounded-2xl bg-white p-4 text-zinc-800 shadow-2xl animate-aparecer [animation-delay:240ms]">
                            <div class="flex items-center justify-between text-xs text-zinc-500">
                                <span>Nueva venta #000245</span>
                                <span class="inline-flex items-center gap-1 text-green-600"><span class="size-1.5 animate-pulse rounded-full bg-green-500"></span> En vivo</span>
                            </div>
                            <div class="mt-1 text-2xl font-semibold">$186.00</div>
                            <div class="text-xs text-zinc-500">La Esquina — Centro · Efectivo</div>
                        </div>
                        <div class="absolute bottom-0 right-0 w-56 rounded-2xl bg-white/95 p-4 text-zinc-800 shadow-2xl animate-aparecer [animation-delay:380ms]">
                            <div class="text-xs text-zinc-500">Gastos / ventas</div>
                            <svg viewBox="0 0 200 110" class="mt-1 w-full">
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#f4f4f5" stroke-width="16" stroke-linecap="round" />
                                <path d="M 20 100 A 80 80 0 0 1 145 38" fill="none" stroke="#16a34a" stroke-width="16" stroke-linecap="round" class="trazo-animado" />
                                <text x="100" y="94" text-anchor="middle" style="font: 600 24px sans-serif" fill="#18181b">54%</text>
                            </svg>
                            <div class="text-center text-xs font-semibold text-green-600">Sano</div>
                        </div>
                    </div>
                </div>

                <div class="relative text-sm text-orange-50/80">© {{ now()->year }} Delidata · Punto de venta y control para restaurantes</div>
            </aside>

            {{-- Formulario --}}
            <main class="flex flex-col items-center justify-center bg-gradient-to-b from-orange-50/60 to-white px-6 py-12 lg:bg-none">
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 lg:hidden" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-orange-500 text-white shadow-md shadow-orange-200">
                        <x-app-logo-icon class="size-6" />
                    </span>
                    <span class="text-xl font-semibold tracking-tight text-zinc-900">Delidata</span>
                </a>

                <div class="w-full max-w-sm animate-aparecer">
                    {{ $slot }}
                </div>

                <a href="{{ route('home') }}" class="mt-10 inline-flex items-center gap-1 text-sm text-zinc-500 transition hover:text-orange-600" wire:navigate>
                    <flux:icon name="arrow-left" variant="micro" /> Volver al inicio
                </a>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @livewireScripts
        @fluxScripts
    </body>
</html>
