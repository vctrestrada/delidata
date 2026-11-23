<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket #{{ $venta->folioFormateado() }} · {{ $venta->restaurante->nombre }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fafafa; font-family: ui-monospace, 'Courier New', monospace; color: #18181b; font-size: 12px; }
        .ticket { width: 80mm; margin: 24px auto; background: #fff; padding: 16px 14px; border: 1px solid #e4e4e7; border-top: 4px solid #f97316; }
        .centro { text-align: center; }
        .negrita { font-weight: 700; }
        .grande { font-size: 18px; }
        .gris { color: #71717a; }
        .naranja { color: #ea580c; }
        .separador { border-top: 1px dashed #a1a1aa; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .der { text-align: right; white-space: nowrap; }
        .cancelada { margin-top: 8px; padding: 6px; border: 2px solid #dc2626; color: #dc2626; text-align: center; font-weight: 700; }
        .acciones { width: 80mm; margin: 0 auto 24px; display: flex; gap: 8px; font-family: system-ui, sans-serif; }
        .acciones button { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #e4e4e7; background: #fff; cursor: pointer; font-size: 14px; }
        .acciones .primario { background: #f97316; border-color: #f97316; color: #fff; font-weight: 600; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { background: #fff; }
            .ticket { margin: 0; border: none; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="centro">
            <div class="negrita grande">{{ $venta->negocio->nombre }}</div>
            <div>{{ $venta->restaurante->nombre }}</div>
            @if ($venta->restaurante->direccion)<div class="gris">{{ $venta->restaurante->direccion }}</div>@endif
            @if ($venta->restaurante->telefono)<div class="gris">Tel. {{ $venta->restaurante->telefono }}</div>@endif
            @if ($venta->negocio->rfc)<div class="gris">RFC {{ $venta->negocio->rfc }}</div>@endif
        </div>

        <div class="separador"></div>

        <table>
            <tr><td>Folio</td><td class="der negrita">#{{ $venta->folioFormateado() }}</td></tr>
            <tr><td>Fecha</td><td class="der">{{ $venta->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td>Atendió</td><td class="der">{{ $venta->vendedor?->name ?? '—' }}</td></tr>
        </table>

        <div class="separador"></div>

        <table>
            @foreach ($venta->detalles as $detalle)
                <tr><td colspan="2">{{ $detalle->nombre }}</td></tr>
                <tr>
                    <td class="gris">{{ $detalle->cantidad }} x ${{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="der">${{ number_format($detalle->importe, 2) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="separador"></div>

        <table>
            <tr><td>Subtotal</td><td class="der">${{ number_format($venta->subtotal, 2) }}</td></tr>
            @if ((float) $venta->descuento > 0)
                <tr><td>Descuento</td><td class="der">-${{ number_format($venta->descuento, 2) }}</td></tr>
            @endif
            <tr class="negrita grande"><td>TOTAL</td><td class="der naranja">${{ number_format($venta->total, 2) }}</td></tr>
            <tr><td>{{ $venta->metodo_pago->etiqueta() }}</td><td class="der">{{ $venta->recibido !== null ? '$'.number_format($venta->recibido, 2) : '' }}</td></tr>
            @if ($venta->cambio !== null)
                <tr><td>Cambio</td><td class="der">${{ number_format($venta->cambio, 2) }}</td></tr>
            @endif
        </table>

        @if ($venta->notas)
            <div class="separador"></div>
            <div>Nota: {{ $venta->notas }}</div>
        @endif

        @if ($venta->estaCancelada())
            <div class="cancelada">VENTA CANCELADA<br><span style="font-weight:400">{{ $venta->motivo_cancelacion }}</span></div>
        @endif

        <div class="separador"></div>
        <div class="centro">¡Gracias por su compra!</div>
        <div class="centro gris" style="margin-top:6px;font-size:10px">Generado con Delidata</div>
    </div>

    <div class="acciones">
        <button type="button" onclick="window.close()">Cerrar</button>
        <button type="button" class="primario" onclick="window.print()">Imprimir</button>
    </div>
</body>
</html>
