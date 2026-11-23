/**
 * Notificaciones en tiempo real de Delidata.
 *
 * El layout deja en <meta name="delidata-tiempo-real"> el canal del usuario
 * y si debe recibir avisos de ventas (solo dueño y encargado). Aquí:
 *  1. Se publica el estado de la conexión para el indicador "En vivo".
 *  2. Se muestra un aviso (toast de Flux) en cualquier pantalla cuando hay
 *     una venta nueva o una cancelación.
 * Las pantallas que muestran datos (dashboard, ventas, gastos) se actualizan
 * solas mediante sus propios listeners de Livewire.
 */
const suscritos = new Set();
const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function avisar(opciones) {
    window.Flux?.toast?.({ duration: 5000, ...opciones });
}

function publicarEstado(estado) {
    window.delidataConexion = estado;
    window.dispatchEvent(new CustomEvent('delidata:conexion', { detail: estado }));
}

function vigilarConexion() {
    const conexion = window.Echo?.connector?.pusher?.connection;

    if (!conexion || conexion.__delidata) {
        return;
    }

    conexion.__delidata = true;
    publicarEstado(conexion.state);
    conexion.bind('state_change', ({ current }) => publicarEstado(current));
}

function suscribir() {
    const meta = document.querySelector('meta[name="delidata-tiempo-real"]');

    if (!meta || !window.Echo) {
        return;
    }

    vigilarConexion();

    const { canal, notificar } = JSON.parse(meta.content);

    if (!canal || !notificar || suscritos.has(canal)) {
        return;
    }

    suscritos.add(canal);

    window.Echo.private(canal)
        .listen('.venta.registrada', (venta) => {
            avisar({
                heading: `Nueva venta #${venta.folio} · ${moneda.format(venta.total)}`,
                text: `${venta.restaurante} · ${venta.vendedor ?? 'Vendedor'} · ${venta.metodo}`,
                variant: 'success',
            });
        })
        .listen('.venta.cancelada', (venta) => {
            avisar({
                heading: `Venta #${venta.folio} cancelada`,
                text: `${venta.restaurante} · ${moneda.format(venta.total)}`,
                variant: 'warning',
            });
        });
}

// En la carga inicial y después de cada navegación con wire:navigate.
document.addEventListener('livewire:navigated', suscribir);
