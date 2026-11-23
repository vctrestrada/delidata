/**
 * Recorridos guiados de Delidata (Driver.js).
 *
 * Cada pantalla que tenga un recorrido incluye el componente <x-recorrido>,
 * que deja en el HTML un bloque [data-recorrido] con los pasos en JSON.
 * - Si el usuario nunca lo ha visto (data-auto="1"), se inicia solo.
 * - Cualquier botón con [data-recorrido-iniciar] lo vuelve a mostrar.
 * - Al cerrarlo se avisa al servidor para no repetirlo automáticamente.
 * - Cada paso tiene "Saltar guía", y el primero ofrece "No volver a mostrar guías"
 *   (desactiva el inicio automático en todas las pantallas para ese usuario).
 */
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

function leerRecorrido() {
    const contenedor = document.querySelector('[data-recorrido]');

    if (!contenedor) {
        return null;
    }

    const json = contenedor.querySelector('script[type="application/json"]');

    return {
        contenedor,
        clave: contenedor.dataset.recorrido,
        automatico: contenedor.dataset.auto === '1',
        pasos: json ? JSON.parse(json.textContent) : [],
    };
}

function enviar(url) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    return fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
    }).catch(() => {});
}

function marcarComoVisto(clave) {
    enviar(`/recorridos/${encodeURIComponent(clave)}`);
}

function boton(texto, clase, alHacerClic) {
    const elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = clase;
    elemento.textContent = texto;
    elemento.addEventListener('click', alHacerClic);

    return elemento;
}

function iniciarRecorrido(recorrido) {
    // Solo se muestran los pasos cuyo elemento existe en la pantalla actual.
    const pasos = recorrido.pasos
        .filter((paso) => !paso.elemento || document.querySelector(paso.elemento))
        .map((paso) => ({
            element: paso.elemento ?? undefined,
            popover: {
                title: paso.titulo,
                description: paso.descripcion,
                side: paso.lado ?? 'bottom',
                align: 'start',
            },
        }));

    if (pasos.length === 0) {
        return;
    }

    const guia = driver({
        steps: pasos,
        showProgress: true,
        progressText: 'Paso {{current}} de {{total}}',
        nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Entendido',
        popoverClass: 'delidata-recorrido',
        overlayColor: '#18181b',
        overlayOpacity: 0.45,
        stagePadding: 6,
        stageRadius: 10,
        onDestroyed: () => marcarComoVisto(recorrido.clave),
        onPopoverRender: (popover, { state }) => {
            // "Saltar guía" en todos los pasos menos el último.
            if (!guia.isLastStep()) {
                popover.footerButtons.prepend(
                    boton('Saltar guía', 'delidata-saltar', () => guia.destroy()),
                );
            }

            // En el primer paso, opción para no ver más guías automáticas.
            if (state.activeIndex === 0) {
                popover.description.after(
                    boton('No volver a mostrar guías automáticamente', 'delidata-no-mostrar', async () => {
                        await enviar('/recorridos/desactivar');
                        guia.destroy();
                        window.Flux?.toast?.({
                            text: 'Listo. Puedes ver cualquier guía con el botón «Ver recorrido» o reactivarlas desde tu menú.',
                        });
                    }),
                );
            }
        },
    });

    guia.drive();
}

function prepararPantalla() {
    const recorrido = leerRecorrido();

    if (!recorrido || !recorrido.automatico || recorrido.contenedor.dataset.iniciado) {
        return;
    }

    recorrido.contenedor.dataset.iniciado = '1';
    setTimeout(() => iniciarRecorrido(recorrido), 450);
}

// livewire:navigated se dispara en la carga inicial y en cada navegación con wire:navigate.
document.addEventListener('livewire:navigated', prepararPantalla);

document.addEventListener('click', (evento) => {
    if (!evento.target.closest('[data-recorrido-iniciar]')) {
        return;
    }

    const recorrido = leerRecorrido();

    if (recorrido) {
        iniciarRecorrido(recorrido);
    }
});
