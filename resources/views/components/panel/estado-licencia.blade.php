@props(['estado'])

<flux:badge :size="$attributes->get('size', 'sm')" :color="$estado->color()" {{ $attributes->except('size') }}>{{ $estado->etiqueta() }}</flux:badge>
