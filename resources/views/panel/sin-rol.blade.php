<x-layouts::app title="Sin acceso asignado">
    <div class="flex flex-1 items-center justify-center">
        <div class="max-w-md space-y-4 rounded-xl border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full border border-zinc-200 dark:border-zinc-700">
                <flux:icon name="lock-closed" variant="outline" class="size-6 text-zinc-600 dark:text-zinc-300" />
            </div>
            <flux:heading size="lg">Tu cuenta aún no tiene un rol</flux:heading>
            <flux:text>
                Pide al administrador de tu restaurante que te asigne un rol para acceder al sistema.
            </flux:text>
        </div>
    </div>
</x-layouts::app>
