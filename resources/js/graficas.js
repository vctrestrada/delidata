/**
 * Gráficas de Delidata con ApexCharts.
 *
 * Uso en Blade:
 *   <div x-data="grafica(@js($configuracion))" wire:ignore></div>
 *
 * La configuración (tipo, series, etiquetas…) se arma en PHP; aquí solo se
 * aplican los estilos comunes (paleta blanco/naranja, tipografía, idioma).
 * Cuando Livewire cambia los datos, el div se vuelve a crear (wire:key) y
 * la gráfica se destruye y se dibuja de nuevo.
 */
import ApexCharts from 'apexcharts';

const PALETA = ['#f97316', '#a1a1aa', '#fdba74', '#52525b', '#fed7aa', '#d4d4d8'];

const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const monedaCompleta = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

/** Formateadores que se pueden pedir desde PHP por nombre (las funciones no viajan en JSON). */
const FORMATOS = {
    moneda: (v) => moneda.format(v ?? 0),
    monedaCompleta: (v) => monedaCompleta.format(v ?? 0),
    porcentaje: (v) => `${Math.round(v ?? 0)}%`,
    entero: (v) => Math.round(v ?? 0).toLocaleString('es-MX'),
    hora: (v) => `${String(v).padStart(2, '0')}:00`,
};

function aplicarFormatos(config) {
    const formato = FORMATOS[config.formato] ?? FORMATOS.entero;

    if (config.chart?.type !== 'radialBar' && config.chart?.type !== 'donut') {
        config.yaxis = { ...(config.yaxis ?? {}), labels: { ...(config.yaxis?.labels ?? {}), formatter: formato } };
    }

    if (config.chart?.type === 'bar' && config.plotOptions?.bar?.horizontal) {
        config.xaxis = { ...(config.xaxis ?? {}), labels: { ...(config.xaxis?.labels ?? {}), formatter: formato } };
        config.yaxis = { labels: { maxWidth: 180 } };
    }

    config.tooltip = { ...(config.tooltip ?? {}), y: { formatter: FORMATOS[config.formatoTooltip ?? config.formato] ?? formato } };

    if (config.chart?.type === 'donut') {
        config.plotOptions = {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: { show: true, label: 'Total', formatter: (w) => formato(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                        value: { formatter: (v) => formato(Number(v)) },
                    },
                },
            },
        };
    }

    if (config.chart?.type === 'radialBar' && config.plotOptions?.radialBar?.dataLabels?.value) {
        config.plotOptions.radialBar.dataLabels.value.formatter = FORMATOS.porcentaje;
    }

    delete config.formato;
    delete config.formatoTooltip;

    return config;
}

function configuracionBase() {
    return {
        colors: PALETA,
        chart: {
            fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 500 },
            defaultLocale: 'es',
            locales: [{
                name: 'es',
                options: {
                    months: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
                    shortMonths: ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
                    days: ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                    shortDays: ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'],
                },
            }],
        },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', markers: { size: 5 } },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: '#71717a', fontSize: '11px' } } },
        noData: { text: 'Sin datos en este periodo', style: { color: '#a1a1aa' } },
    };
}

function combinar(base, extra) {
    const resultado = { ...base };
    for (const [clave, valor] of Object.entries(extra)) {
        resultado[clave] = valor && typeof valor === 'object' && !Array.isArray(valor) && base[clave] && typeof base[clave] === 'object'
            ? combinar(base[clave], valor)
            : valor;
    }
    return resultado;
}

window.grafica = (config) => ({
    instancia: null,

    init() {
        const opciones = aplicarFormatos(combinar(configuracionBase(), structuredClone(config)));
        this.instancia = new ApexCharts(this.$el, opciones);
        this.instancia.render();
    },

    destroy() {
        this.instancia?.destroy();
    },
});
