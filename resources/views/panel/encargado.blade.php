<x-layouts::app title="Mi sucursal">
    <div class="flex flex-col gap-6">
        <x-panel.encabezado
            titulo="Hola, {{ auth()->user()->name }}"
            descripcion="Operación diaria de tu sucursal."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-panel.modulo icono="user-group" entrega="3"
                titulo="Vendedores"
                descripcion="Personal asignado a la sucursal." />
            <x-panel.modulo icono="receipt-percent" entrega="5"
                titulo="Ventas del día"
                descripcion="Consulta las ventas registradas en la sucursal." />
            <x-panel.modulo icono="banknotes" entrega="6"
                titulo="Gastos"
                descripcion="Registra los gastos de la sucursal." />
        </div>
    </div>
</x-layouts::app>
