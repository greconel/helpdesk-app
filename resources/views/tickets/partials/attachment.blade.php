<div class="flex flex-col gap-1">
    <div class="flex items-center gap-2">

        {{-- Icon op basis van bestandstype --}}
        @if($attachment->isImage())
            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        @elseif($attachment->isPdf())
            <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        @elseif($attachment->isWord())
            <svg class="w-3.5 h-3.5 text-blue-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        @elseif($attachment->isExcel())
            <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        @else
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
        @endif

        {{-- Bestandsnaam als link --}}
        <a href="{{ route('attachments.download', $attachment) }}"
           class="text-xs text-blue-600 hover:text-blue-700 hover:underline truncate max-w-[220px]"
           title="{{ $attachment->original_filename }}">
            {{ $attachment->original_filename }}
        </a>

        {{-- Bestandsgrootte --}}
        <span class="text-xs text-gray-400 flex-shrink-0">
            {{ $attachment->getFormattedSize() }}
        </span>

        {{-- Bekijk knop voor PDF en afbeeldingen --}}
        @if($attachment->isImage() || $attachment->isPdf())
            <a href="{{ route('attachments.show', $attachment) }}"
               target="_blank"
               class="text-xs text-gray-500 hover:text-gray-700 flex-shrink-0"
               title="Bekijk in browser">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        @endif
    </div>

    {{-- Inline preview voor afbeeldingen --}}
    @if($attachment->isImage())
        <div class="ml-5 mt-1">
            <a href="{{ route('attachments.show', $attachment) }}" target="_blank">
                <img src="{{ $attachment->getUrl() }}"
                     alt="{{ $attachment->original_filename }}"
                     class="max-w-[280px] max-h-[180px] rounded-lg border border-gray-200 object-contain hover:opacity-90 transition-opacity">
            </a>
        </div>
    @endif
</div>