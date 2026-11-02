@php
    $types = [
        'success' => [
            'icon' => 'fa-circle-check',
            'class' => 'border-green-200 bg-green-50 text-green-800',
            'iconColor' => 'text-green-600',
        ],
        'danger' => [
            'icon' => 'fa-circle-exclamation',
            'class' => 'border-red-200 bg-red-50 text-red-800',
            'iconColor' => 'text-red-600',
        ],
        'warning' => [
            'icon' => 'fa-triangle-exclamation',
            'class' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
            'iconColor' => 'text-yellow-600',
        ],
        'info' => [
            'icon' => 'fa-circle-info',
            'class' => 'border-blue-200 bg-blue-50 text-blue-800',
            'iconColor' => 'text-blue-600',
        ],
    ];
@endphp

<div class="mb-6 space-y-3">
    @foreach ($types as $type => $config)
        @if (session($type))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                class="flex items-center justify-between rounded-xl border p-4 {{ $config['class'] }}">

                <div class="flex items-center gap-3">
                    <i class="fa-solid {{ $config['icon'] }} {{ $config['iconColor'] }}"></i>
                    <span>{{ session($type) }}</span>
                </div>

                <button @click="show = false" class="text-lg opacity-60 hover:opacity-100">
                    &times;
                </button>
            </div>
        @endif
    @endforeach
</div>
