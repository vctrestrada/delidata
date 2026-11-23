<x-layouts::app title="Punto de venta">
    <div class="flex flex-col gap-6">
        <x-panel.encabezado
            titulo="Hola, {{ auth()->user()->name }}"
            :descripcion="'Vendes en '.auth()->user()->restaurante?->nombre.'.'"
        />

        <div class="grid gap-4 md:grid-cols-2">
            <x-panel.modulo icono="shopping-cart" entrega="5" titulo="Nueva venta"
                descripcion="Selecciona productos, cobra y genera el ticket." />
            <x-panel.modulo icono="clock" entrega="5" titulo="Mis ventas de hoy"
                descripcion="Historial de lo que has vendido en el turno." />
        </div>
    </div>
</x-layouts::app>
