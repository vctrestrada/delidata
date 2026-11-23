<x-layouts::app title="Administración">
    <div class="flex flex-col gap-6">
        <x-panel.encabezado
            titulo="Hola, {{ auth()->user()->name }}"
            descripcion="Administra los restaurantes que usan Delidata y el estado de sus licencias."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-panel.modulo icono="building-storefront" entrega="2"
                titulo="Restaurantes"
                descripcion="Alta de restaurantes y de su dueño." />
            <x-panel.modulo icono="key" entrega="2"
                titulo="Licencias"
                descripcion="Quién tiene la licencia activa, vencida o suspendida." />
            <x-panel.modulo icono="users" entrega="2"
                titulo="Dueños"
                descripcion="Cuentas de los dueños de cada restaurante." />
        </div>
    </div>
</x-layouts::app>
