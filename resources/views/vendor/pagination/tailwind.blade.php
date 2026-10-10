@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegación de páginas" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full px-2 py-4">
        {{-- Texto de conteo en español --}}
        <div class="text-xs sm:text-sm text-slate-400 font-medium">
            Mostrando
            <span class="font-bold text-slate-100">{{ $paginator->firstItem() }}</span>
            al
            <span class="font-bold text-slate-100">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-bold text-blue-400">{{ $paginator->total() }}</span>
            preguntas registradas
        </div>

        {{-- Botones de Navegación Oscuros y Elegantes --}}
        <div class="inline-flex items-center gap-2 p-1.5 bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl backdrop-blur-md">
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

            {{-- Elementos / Números de página (Desktop) --}}
            <div class="hidden md:flex items-center gap-1 mx-1">
                @foreach ($elements as $element)
                    {{-- Separador "..." --}}
                    @if (is_string($element))
                        <span class="px-3 py-1.5 text-xs font-semibold text-slate-500 select-none">{{ $element }}</span>
                    @endif

                    {{-- Enlaces de Páginas --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-9 h-9 flex items-center justify-center text-xs font-bold text-white bg-blue-600 border border-blue-400 rounded-xl shadow-lg shadow-blue-500/30">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all duration-150 border border-transparent hover:border-slate-700">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Indicador numérico para móvil --}}
            <div class="md:hidden px-2 text-xs font-semibold text-slate-400">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </div>

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
        </div>
    </nav>
@endif
