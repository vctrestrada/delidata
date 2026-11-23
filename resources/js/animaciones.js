/**
 * Animaciones de Delidata.
 *
 * - [data-contador]: el número cuenta desde 0 hasta su valor ("$12,345.67", "26", "54%").
 * - .revelar: el elemento aparece suavemente cuando entra en pantalla al hacer scroll.
 *
 * Se respetan las preferencias de "reducir movimiento" del sistema.
 */
const reducirMovimiento = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function animarContador(elemento) {
    if (elemento.dataset.animado) {
        return;
    }

    elemento.dataset.animado = '1';

    const texto = elemento.textContent.trim();
    const coincidencia = texto.match(/^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/);

    if (!coincidencia || reducirMovimiento()) {
        return;
    }

    const [, prefijo, numero, sufijo] = coincidencia;
    const destino = parseFloat(numero.replace(/,/g, ''));
    const decimales = numero.includes('.') ? numero.split('.')[1].length : 0;
    const formato = new Intl.NumberFormat('es-MX', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
    const duracion = 900;
    const inicio = performance.now();

    const paso = (ahora) => {
        const avance = Math.min(1, (ahora - inicio) / duracion);
        const suavizado = 1 - Math.pow(1 - avance, 3);
        elemento.textContent = `${prefijo}${formato.format(destino * suavizado)}${sufijo}`;

        if (avance < 1) {
            requestAnimationFrame(paso);
        } else {
            elemento.textContent = texto;
        }
    };

    requestAnimationFrame(paso);
}

const observador = 'IntersectionObserver' in window
    ? new IntersectionObserver((entradas) => {
        for (const entrada of entradas) {
            if (entrada.isIntersecting) {
                entrada.target.classList.add('revelado');
                observador.unobserve(entrada.target);
            }
        }
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' })
    : null;

function prepararAnimaciones() {
    document.querySelectorAll('[data-contador]').forEach(animarContador);

    document.querySelectorAll('.revelar:not(.revelado)').forEach((elemento) => {
        if (!observador || reducirMovimiento()) {
            elemento.classList.add('revelado');
        } else {
            observador.observe(elemento);
        }
    });
}

document.addEventListener('livewire:navigated', prepararAnimaciones);
document.addEventListener('DOMContentLoaded', prepararAnimaciones);

// Cuando Livewire agrega elementos nuevos (por ejemplo, al cambiar un filtro).
document.addEventListener('livewire:init', () => {
    window.Livewire?.hook?.('morphed', () => requestAnimationFrame(prepararAnimaciones));
});
