@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegación de páginas" class="flex items-center justify-between gap-4 w-full px-2 py-4">
        {{-- Botón Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-slate-950/50 rounded-xl cursor-not-allowed flex items-center gap-2 select-none border border-slate-800/60">
                <i class="fa-solid fa-chevron-left text-xs"></i>
                <span>Página Anterior</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-200 hover:text-white bg-slate-800/90 hover:bg-blue-600 rounded-xl transition-all duration-200 flex items-center gap-2 border border-slate-700/80 hover:border-blue-500 shadow-sm active:scale-95">
                <i class="fa-solid fa-chevron-left text-xs"></i>
                <span>Página Anterior</span>
            </a>
        @endif

        {{-- Botón Siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-200 hover:text-white bg-slate-800/90 hover:bg-blue-600 rounded-xl transition-all duration-200 flex items-center gap-2 border border-slate-700/80 hover:border-blue-500 shadow-sm active:scale-95">
                <span>Página Siguiente</span>
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        @else
            <span class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-slate-950/50 rounded-xl cursor-not-allowed flex items-center gap-2 select-none border border-slate-800/60">
                <span>Página Siguiente</span>
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </span>
        @endif
    </nav>
@endif
