@props([
    'type' => 'info',
])

@php
    $types = [
        'info' => 'alert alert-info',
        'success' => 'alert alert-success',
        'warning' => 'alert alert-warning',
        'error' => 'alert alert-danger',
    ];

    $classes = $types[$type] ?? $types['info'];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
