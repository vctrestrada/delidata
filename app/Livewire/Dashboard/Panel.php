<?php

namespace App\Livewire\Dashboard;

use App\Enums\MetodoPago;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Livewire\Concerns\EscuchaTiempoReal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard de ventas contra gastos.
 *  - Dueño: todo el negocio, con filtro y comparación por restaurante.
 *  - Encargado: solo su restaurante.
 */
class Panel extends Component
{
    use AlcanceDelNegocio;
    use EscuchaTiempoReal;

    /** 7 | 30 | mes | mes-pasado | personalizado */
    #[Url(except: '30')]
    public string $periodo = '30';

    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Url(except: '')]
    public string $restaurante = '';

    /** Ranking de productos por importe o por piezas. */
    public string $rankingPor = 'importe';

    /** Proporción gastos/ventas a partir de la cual el velocímetro se pone naranja o rojo. */
    public const ZONA_PRECAUCION = 60;

    public const ZONA_PELIGRO = 85;

    public function mount(): void
    {
        $this->aplicarPeriodo();
    }

    public function updatedPeriodo(): void
    {
        $this->aplicarPeriodo();
    }

    public function updatedDesde(): void
    {
        $this->periodo = 'personalizado';
    }

    public function updatedHasta(): void
    {
        $this->periodo = 'personalizado';
    }

    private function aplicarPeriodo(): void
    {
        [$desde, $hasta] = match ($this->periodo) {
            '7' => [today()->subDays(6), today()],
            'mes' => [today()->startOfMonth(), today()],
            'mes-pasado' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'personalizado' => [Carbon::parse($this->desde ?: today()->subDays(29)), Carbon::parse($this->hasta ?: today())],
            default => [today()->subDays(29), today()],
        };

        $this->desde = $desde->toDateString();
        $this->hasta = max($hasta, $desde)->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas base
    |--------------------------------------------------------------------------
    */

    /** @return array{0: Carbon, 1: Carbon} */
    private function rango(): array
    {
        return [Carbon::parse($this->desde)->startOfDay(), Carbon::parse($this->hasta)->endOfDay()];
    }

    /** Mismo número de días, justo antes del periodo actual. @return array{0: Carbon, 1: Carbon} */
    private function rangoAnterior(): array
    {
        [$desde, $hasta] = $this->rango();
        $dias = (int) $desde->diffInDays($hasta) + 1;

        return [$desde->copy()->subDays($dias), $desde->copy()->subSecond()];
    }

    private function conRestaurante(Builder $q): Builder
    {
        return $q->when(! $this->esEncargado() && $this->restaurante !== '', fn (Builder $w) => $w->where('restaurante_id', (int) $this->restaurante));
    }

    private function ventas(array $rango): Builder
    {
        return $this->conRestaurante($this->consultaVentas())->completadas()->whereBetween('created_at', $rango);
    }

    private function gastos(array $rango): Builder
    {
        return $this->conRestaurante($this->consultaGastos())
            ->whereDate('fecha', '>=', $rango[0]->toDateString())
            ->whereDate('fecha', '<=', $rango[1]->toDateString());
    }

    /** Expresión SQL de la hora según el motor (PostgreSQL en desarrollo, SQLite en pruebas). */
    private function sqlHora(string $columna): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(HOUR FROM {$columna})",
            'mysql', 'mariadb' => "HOUR({$columna})",
            default => "CAST(strftime('%H', {$columna}) AS INTEGER)",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Indicadores
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function indicadores(): array
    {
        $actual = $this->totales($this->rango());
        $anterior = $this->totales($this->rangoAnterior());

        $variacion = fn (float $a, float $b) => $b > 0 ? (int) round(($a - $b) / $b * 100) : null;

        return [
            ...$actual,
            'variacionVentas' => $variacion($actual['ventas'], $anterior['ventas']),
            'variacionGastos' => $variacion($actual['gastos'], $anterior['gastos']),
            'variacionUtilidad' => $anterior['utilidad'] != 0 ? (int) round(($actual['utilidad'] - $anterior['utilidad']) / abs($anterior['utilidad']) * 100) : null,
            'variacionTickets' => $variacion($actual['tickets'], $anterior['tickets']),
        ];
    }

    private function totales(array $rango): array
    {
        $ventas = $this->ventas($rango)->selectRaw('COALESCE(SUM(total), 0) AS total, COUNT(*) AS tickets')->first();
        $gastos = (float) $this->gastos($rango)->sum('monto');
        $totalVentas = (float) $ventas->total;
        $tickets = (int) $ventas->tickets;

        return [
            'ventas' => $totalVentas,
            'gastos' => $gastos,
            'utilidad' => $totalVentas - $gastos,
            'tickets' => $tickets,
            'ticketPromedio' => $tickets ? $totalVentas / $tickets : 0,
            'proporcion' => $totalVentas > 0 ? round($gastos / $totalVentas * 100, 1) : ($gastos > 0 ? 100 : 0),
        ];
    }

    /** Estado del velocímetro según la proporción de gastos sobre ventas. */
    public function zona(float $proporcion): array
    {
        return match (true) {
            $proporcion >= self::ZONA_PELIGRO => ['color' => '#dc2626', 'texto' => 'Peligro', 'mensaje' => 'Los gastos se están comiendo casi todas las ventas.', 'clase' => 'text-red-600'],
            $proporcion >= self::ZONA_PRECAUCION => ['color' => '#f97316', 'texto' => 'Precaución', 'mensaje' => 'Hay utilidad, pero el margen es estrecho. Revisa los gastos más grandes.', 'clase' => 'text-orange-600'],
            default => ['color' => '#16a34a', 'texto' => 'Sano', 'mensaje' => 'Por cada $100 que vendes, te quedan más de $'.(100 - self::ZONA_PRECAUCION).' después de gastos.', 'clase' => 'text-green-600'],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Gráficas (configuración de ApexCharts armada en PHP)
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function graficaVelocimetro(): array
    {
        $proporcion = (float) $this->indicadores['proporcion'];

        return $this->velocimetro($proporcion, 260);
    }

    /** Gauge semicircular reutilizable (general y por restaurante). */
    public function velocimetro(float $proporcion, int $alto = 180): array
    {
        return [
            'chart' => ['type' => 'radialBar', 'height' => $alto, 'sparkline' => ['enabled' => true]],
            'series' => [min(100, round($proporcion, 1))],
            'colors' => [$this->zona($proporcion)['color']],
            'plotOptions' => [
                'radialBar' => [
                    'startAngle' => -90,
                    'endAngle' => 90,
                    'hollow' => ['size' => '62%'],
                    'track' => ['background' => '#f4f4f5', 'strokeWidth' => '100%'],
                    'dataLabels' => [
                        'name' => ['show' => false],
                        'value' => ['offsetY' => -6, 'fontSize' => $alto > 200 ? '30px' : '20px', 'fontWeight' => 600, 'color' => '#18181b'],
                    ],
                ],
            ],
            'stroke' => ['lineCap' => 'round'],
            'labels' => ['Gastos / ventas'],
            'formato' => 'porcentaje',
        ];
    }

    #[Computed]
    public function graficaTendencia(): array
    {
        [$desde, $hasta] = $this->rango();

        $ventas = $this->ventas($this->rango())
            ->selectRaw('DATE(created_at) AS dia, SUM(total) AS total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'dia');

        $gastos = $this->gastos($this->rango())
            ->selectRaw('fecha AS dia, SUM(monto) AS total')
            ->groupBy('fecha')
            ->get()
            ->mapWithKeys(fn ($g) => [Carbon::parse($g->dia)->toDateString() => $g->total]);

        $dias = collect();
        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $dias->push($dia->toDateString());
        }

        return [
            'chart' => ['type' => 'area', 'height' => 300],
            'series' => [
                ['name' => 'Ventas', 'data' => $dias->map(fn ($d) => round((float) ($ventas[$d] ?? 0), 2))->all()],
                ['name' => 'Gastos', 'data' => $dias->map(fn ($d) => round((float) ($gastos[$d] ?? 0), 2))->all()],
            ],
            'xaxis' => ['type' => 'datetime', 'categories' => $dias->all(), 'labels' => ['datetimeUTC' => false, 'format' => 'dd MMM']],
            'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.02]],
            'tooltip' => ['x' => ['format' => 'dddd dd MMM']],
            'formato' => 'moneda',
            'formatoTooltip' => 'monedaCompleta',
        ];
    }

    /** Ventas, gastos y utilidad por restaurante (solo dueño). @return Collection<int, array> */
    #[Computed]
    public function porRestaurante(): Collection
    {
        $ventas = $this->ventas($this->rango())->selectRaw('restaurante_id, SUM(total) AS total')->groupBy('restaurante_id')->pluck('total', 'restaurante_id');
        $gastos = $this->gastos($this->rango())->selectRaw('restaurante_id, SUM(monto) AS total')->groupBy('restaurante_id')->pluck('total', 'restaurante_id');

        return $this->restaurantesDisponibles()
            ->when(! $this->esEncargado() && $this->restaurante !== '', fn ($c) => $c->where('id', (int) $this->restaurante))
            ->map(function ($r) use ($ventas, $gastos) {
                $v = (float) ($ventas[$r->id] ?? 0);
                $g = (float) ($gastos[$r->id] ?? 0);

                return [
                    'id' => $r->id,
                    'nombre' => $r->nombre,
                    'ventas' => $v,
                    'gastos' => $g,
                    'utilidad' => $v - $g,
                    'proporcion' => $v > 0 ? round($g / $v * 100, 1) : ($g > 0 ? 100 : 0),
                ];
            })
            ->values();
    }

    #[Computed]
    public function graficaRestaurantes(): array
    {
        $filas = $this->porRestaurante;

        return [
            'chart' => ['type' => 'bar', 'height' => max(220, 70 * $filas->count() + 80)],
            'plotOptions' => ['bar' => ['horizontal' => true, 'barHeight' => '70%', 'borderRadius' => 4]],
            'series' => [
                ['name' => 'Ventas', 'data' => $filas->map(fn ($f) => round($f['ventas'], 2))->all()],
                ['name' => 'Gastos', 'data' => $filas->map(fn ($f) => round($f['gastos'], 2))->all()],
                ['name' => 'Utilidad', 'data' => $filas->map(fn ($f) => round($f['utilidad'], 2))->all()],
            ],
            'colors' => ['#f97316', '#a1a1aa', '#16a34a'],
            'xaxis' => ['categories' => $filas->pluck('nombre')->all()],
            'formato' => 'moneda',
            'formatoTooltip' => 'monedaCompleta',
        ];
    }

    #[Computed]
    public function graficaProductos(): array
    {
        $campo = $this->rankingPor === 'piezas' ? 'SUM(venta_detalles.cantidad)' : 'SUM(venta_detalles.importe)';

        $top = DB::table('venta_detalles')
            ->joinSub($this->ventas($this->rango())->select('id'), 'v', 'v.id', '=', 'venta_detalles.venta_id')
            ->selectRaw("venta_detalles.nombre, {$campo} AS valor")
            ->groupBy('venta_detalles.nombre')
            ->orderByDesc('valor')
            ->limit(10)
            ->get();

        return [
            'chart' => ['type' => 'bar', 'height' => 340],
            'plotOptions' => ['bar' => ['horizontal' => true, 'barHeight' => '65%', 'borderRadius' => 4, 'distributed' => true]],
            'series' => [['name' => $this->rankingPor === 'piezas' ? 'Piezas' : 'Ventas', 'data' => $top->map(fn ($p) => round((float) $p->valor, 2))->all()]],
            'xaxis' => ['categories' => $top->pluck('nombre')->all()],
            'colors' => ['#ea580c', '#f97316', '#fb923c', '#fdba74', '#fed7aa', '#d4d4d8', '#d4d4d8', '#d4d4d8', '#d4d4d8', '#d4d4d8'],
            'legend' => ['show' => false],
            'formato' => $this->rankingPor === 'piezas' ? 'entero' : 'moneda',
            'formatoTooltip' => $this->rankingPor === 'piezas' ? 'entero' : 'monedaCompleta',
        ];
    }

    #[Computed]
    public function graficaHoras(): array
    {
        $hora = $this->sqlHora('created_at');

        $porHora = $this->ventas($this->rango())
            ->selectRaw("{$hora} AS hora, COUNT(*) AS tickets")
            ->groupByRaw($hora)
            ->pluck('tickets', 'hora')
            ->mapWithKeys(fn ($v, $h) => [(int) $h => (int) $v]);

        $horas = $porHora->isEmpty() ? range(8, 22) : range($porHora->keys()->min(), $porHora->keys()->max());
        $maximo = max(1, $porHora->max() ?? 1);

        return [
            'chart' => ['type' => 'bar', 'height' => 260],
            'plotOptions' => ['bar' => ['columnWidth' => '70%', 'borderRadius' => 3, 'distributed' => true]],
            'series' => [['name' => 'Ventas', 'data' => array_map(fn ($h) => $porHora[$h] ?? 0, $horas)]],
            'xaxis' => ['categories' => array_map(fn ($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00', $horas)],
            // Las horas con más ventas se pintan más naranjas.
            'colors' => array_map(fn ($h) => ($porHora[$h] ?? 0) >= $maximo * 0.75 ? '#f97316' : (($porHora[$h] ?? 0) >= $maximo * 0.4 ? '#fdba74' : '#e4e4e7'), $horas),
            'legend' => ['show' => false],
            'formato' => 'entero',
        ];
    }

    #[Computed]
    public function graficaMetodos(): array
    {
        $porMetodo = $this->ventas($this->rango())
            ->selectRaw('metodo_pago, SUM(total) AS total')
            ->groupBy('metodo_pago')
            ->pluck('total', 'metodo_pago');

        $metodos = collect(MetodoPago::cases());

        return [
            'chart' => ['type' => 'donut', 'height' => 280],
            'series' => $metodos->map(fn (MetodoPago $m) => round((float) ($porMetodo[$m->value] ?? 0), 2))->all(),
            'labels' => $metodos->map(fn (MetodoPago $m) => $m->etiqueta())->all(),
            'colors' => ['#f97316', '#52525b', '#fdba74'],
            'legend' => ['position' => 'bottom', 'horizontalAlign' => 'center'],
            'stroke' => ['width' => 2, 'colors' => ['#fff']],
            'formato' => 'moneda',
            'formatoTooltip' => 'monedaCompleta',
        ];
    }

    protected function eventosTiempoReal(): array
    {
        return ['venta.registrada', 'venta.cancelada', 'gastos.actualizados'];
    }

    public function render()
    {
        return view('livewire.dashboard.panel')->title('Dashboard');
    }
}
