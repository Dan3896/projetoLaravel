@props(['href' => null])

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-medium inline-block text-center']) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => 'bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium inline-block text-center']) }}>
        {{ $slot }}
    </button>
@endif
