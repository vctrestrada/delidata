<x-layouts::app title="Mi negocio">
    <div class="flex flex-col gap-6">
        <x-panel.encabezado
            titulo="Hola, {{ auth()->user()->name }}"
            descripcion="Resumen de tu restaurante y de todas sus sucursales."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-panel.modulo icono="map-pin" entrega="3"
                titulo="Sucursales"
                descripcion="Crea sucursales y asigna a sus encargados." />
            <x-panel.modulo icono="book-open" entrega="4"
                titulo="Productos y menú"
                descripcion="Catálogo del restaurante y precios por sucursal." />
            <x-panel.modulo icono="banknotes" entrega="6"
                titulo="Gastos"
                descripcion="Registro de gastos por sucursal." />
            <x-panel.modulo icono="chart-bar" entrega="7" class="md:col-span-2 xl:col-span-3"
                titulo="Dashboard del negocio"
                descripcion="Velocímetro de gastos contra ventas y gráficas por sucursal." />
        </div>
    </div>
</x-layouts::app>
