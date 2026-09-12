@props([
    'title' => config('app.name', 'Beryl'),
    'description' => null,
])

@php
    $displayTitle = ($title === 'Coolify' || empty($title)) ? config('app.name', 'Beryl') : $title;
@endphp

<section class="auth-shell application-settings-form">
    <div class="auth-shell-content">
        <div class="auth-card">
            <div class="auth-card-heading">
                <div class="flex justify-center mb-3">
                    <a href="/" {{ wireNavigate() }}>
                        <img src="/beryl-logo.png" alt="Beryl" class="size-12 drop-shadow-sm hover:opacity-90 transition-opacity" />
                    </a>
                </div>
                <h1>{{ $displayTitle }}</h1>
                @if ($description)
                    <p>{{ $description }}</p>
                @endif
            </div>

            <div class="auth-card-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="auth-card-footer">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</section>
