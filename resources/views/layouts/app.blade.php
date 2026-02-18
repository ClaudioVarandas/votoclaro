<!DOCTYPE html>
<html lang="pt" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @stack('seo')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sand-50 text-sand-900 font-sans antialiased dark:bg-sand-950 dark:text-sand-100">
    {{-- Skip link --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:rounded-lg focus:bg-republic-600 focus:px-4 focus:py-2 focus:text-white">{{ __('ui.skip_to_content') }}</a>

    {{-- Navigation --}}
    <nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-sand-200 bg-sand-50/90 backdrop-blur-sm dark:border-sand-800 dark:bg-sand-950/90">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="font-serif text-xl font-bold tracking-tight text-republic-700 dark:text-republic-400">VotoClaro</span>
                </a>

                {{-- Desktop nav --}}
                <div class="hidden items-center gap-1 sm:flex">
                    @foreach (['dashboard' => 'dashboard', 'initiatives' => 'initiatives.index', 'parties' => 'parties.index', 'about' => 'about'] as $key => $routeName)
                        <a href="{{ route($routeName) }}"
                           @php $isActive = str_contains($routeName, '.') ? request()->routeIs(str($routeName)->before('.') . '.*') : request()->routeIs($routeName); @endphp
                           class="rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-sand-100 hover:text-republic-700 dark:hover:bg-sand-900 dark:hover:text-republic-400 {{ $isActive ? 'bg-sand-100 text-republic-700 dark:bg-sand-900 dark:text-republic-400' : 'text-sand-600 dark:text-sand-400' }}">
                            {{ __('ui.nav.' . $key) }}
                        </a>
                    @endforeach

                    {{-- Dark mode toggle --}}
                    <button @click="darkMode = !darkMode" class="ml-2 rounded-lg p-2 text-sand-500 transition-colors hover:bg-sand-100 dark:text-sand-400 dark:hover:bg-sand-900" :aria-label="darkMode ? '{{ __('ui.light_mode') }}' : '{{ __('ui.dark_mode') }}'">
                        <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                </div>

                {{-- Mobile hamburger --}}
                <div class="flex items-center gap-2 sm:hidden">
                    <button @click="darkMode = !darkMode" class="rounded-lg p-2 text-sand-500 dark:text-sand-400">
                        <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <button @click="open = !open" class="rounded-lg p-2 text-sand-500 dark:text-sand-400" aria-label="{{ __('ui.menu') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Mobile menu --}}
            <div x-show="open" x-cloak x-transition class="border-t border-sand-200 pb-4 sm:hidden dark:border-sand-800">
                @foreach (['dashboard' => 'dashboard', 'initiatives' => 'initiatives.index', 'parties' => 'parties.index', 'about' => 'about'] as $key => $routeName)
                    <a href="{{ route($routeName) }}"
                       @php $isActive = str_contains($routeName, '.') ? request()->routeIs(str($routeName)->before('.') . '.*') : request()->routeIs($routeName); @endphp
                       class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-sand-100 dark:hover:bg-sand-900 {{ $isActive ? 'text-republic-700 dark:text-republic-400' : 'text-sand-600 dark:text-sand-400' }}">
                        {{ __('ui.nav.' . $key) }}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

    {{-- Main content --}}
    <main id="main-content" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-sand-200 bg-sand-100 dark:border-sand-800 dark:bg-sand-900">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.footer.navigation') }}</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="{{ route('dashboard') }}" class="text-sm text-sand-600 transition-colors hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.nav.dashboard') }}</a></li>
                        <li><a href="{{ route('initiatives.index') }}" class="text-sm text-sand-600 transition-colors hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.nav.initiatives') }}</a></li>
                        <li><a href="{{ route('parties.index') }}" class="text-sm text-sand-600 transition-colors hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.nav.parties') }}</a></li>
                        <li><a href="{{ route('about') }}" class="text-sm text-sand-600 transition-colors hover:text-republic-700 dark:text-sand-400 dark:hover:text-republic-400">{{ __('ui.nav.about') }}</a></li>
                    </ul>
                </div>

                {{-- Information --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.footer.information') }}</h3>
                    <ul class="mt-4 space-y-2">
                        <li><span class="text-sm text-sand-400 dark:text-sand-600">{{ __('ui.footer.open_data') }}</span></li>
                        <li><span class="text-sm text-sand-400 dark:text-sand-600">{{ __('ui.footer.methodology') }}</span></li>
                        <li><span class="text-sm text-sand-400 dark:text-sand-600">{{ __('ui.footer.privacy') }}</span></li>
                    </ul>
                </div>

                {{-- About --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-sand-500 dark:text-sand-400">{{ __('ui.nav.about') }}</h3>
                    <p class="mt-4 text-sm text-sand-600 dark:text-sand-400">{{ __('ui.footer.about_text') }}</p>
                </div>
            </div>

            {{-- Bottom bar --}}
            <div class="mt-10 flex flex-col items-center justify-between gap-2 border-t border-sand-200 pt-6 text-xs text-sand-500 sm:flex-row dark:border-sand-800 dark:text-sand-500">
                <p>&copy; {{ date('Y') }} VotoClaro. {{ __('ui.footer.rights') }}</p>
                <p>{{ __('ui.footer.attribution') }}</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
