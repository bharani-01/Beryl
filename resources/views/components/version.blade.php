@php($version = env('BERYL_VERSION') ?: config('app.version', '1.0.0'))

<a {{ $attributes->merge(['class' => 'text-xs cursor-pointer opacity-90 hover:opacity-100 dark:hover:text-white hover:text-black']) }}
    href="https://github.com/bharani-01/Beryl" target="_blank">
    v{{ ltrim($version, 'v') }}
</a>

