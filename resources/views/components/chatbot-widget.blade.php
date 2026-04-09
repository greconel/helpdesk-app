<style>
.chat-markdown p { margin-bottom: 0.5rem; }
.chat-markdown ul { list-style: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.chat-markdown ol { list-style: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.chat-markdown li { margin-bottom: 0.25rem; }
.chat-markdown strong { font-weight: 600; }
.chat-markdown code { background: #e5e7eb; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.8rem; }
.chat-markdown h1,
.chat-markdown h2,
.chat-markdown h3 { font-weight: 600; margin-bottom: 0.5rem; }
</style>

<div x-data="chatbot()" class="fixed bottom-6 right-6 z-50">

    {{-- Toggle knop --}}
    <button @click="open = !open"
        class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white
               rounded-full shadow-lg flex items-center justify-center
               transition-all duration-200">

        {{-- Chat icoon (gesloten) --}}
        <svg x-show="!open" class="w-6 h-6" fill="none"
             stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03
                     8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72
                     C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9
                     3.582 9 8z"/>
        </svg>

        {{-- Sluit icoon (open) --}}
        <svg x-show="open" class="w-6 h-6" fill="none"
             stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Chat venster --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="absolute bottom-16 right-0 w-80 bg-white rounded-xl
                shadow-2xl border border-gray-200 flex flex-col"
         style="height: 480px; display: none;">

        {{-- Header --}}
        <div class="bg-blue-600 text-white px-4 py-3 rounded-t-xl
                    flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-sm">AI Assistent</h3>
                <p class="text-xs text-blue-200">
                    Stel een vraag over het systeem
                </p>
            </div>
            {{-- Reset knop --}}
            <button @click="resetChat()"
                title="Nieuw gesprek starten"
                class="text-blue-200 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582
                             9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0
                             01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        {{-- Berichten --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3"
             x-ref="messages">

            {{-- Welkomstbericht --}}
            <div class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-100
                            flex items-center justify-center flex-shrink-0 mt-1">
                    <span class="text-xs font-bold text-blue-600">AI</span>
                </div>
                <div class="chat-markdown bg-gray-100 text-gray-800 text-sm
                            px-3 py-2 rounded-2xl rounded-tl-sm max-w-[85%]">
                    Hallo! Stel gerust een vraag over het helpdesk systeem.
                </div>
            </div>

            {{-- Dynamische berichten --}}
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' 
                                ? 'flex items-start justify-end gap-2' 
                                : 'flex items-start gap-2'">

                    {{-- AI avatar --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="w-7 h-7 rounded-full bg-blue-100
                                    flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-xs font-bold text-blue-600">AI</span>
                        </div>
                    </template>

                    {{-- Bericht ballon --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="chat-markdown bg-gray-100 text-gray-800 text-sm
                                    px-3 py-2 rounded-2xl rounded-tl-sm max-w-[85%]"
                            x-html="renderMarkdown(msg)">
                        </div>
                    </template>

                    <template x-if="msg.role === 'user'">
                        <div class="bg-blue-100 text-blue-900 text-sm
                                    px-3 py-2 rounded-2xl rounded-tr-sm max-w-[85%]"
                            x-text="msg.content">
                        </div>
                    </template>

                </div>
            </template>

            {{-- Ticket aanmaken knop --}}
            <div x-show="showTicketButton" class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-100
                            flex items-center justify-center flex-shrink-0 mt-1">
                    <span class="text-xs font-bold text-blue-600">AI</span>
                </div>
                <a href="{{ route('tickets.create') }}"
                   class="bg-blue-50 border border-blue-200 text-blue-700
                          rounded-2xl px-3 py-2 text-sm hover:bg-blue-100
                          transition-colors">
                    📝 Ticket aanmaken
                </a>
            </div>

            {{-- Laad indicator --}}
            <div x-show="loading" class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-100
                            flex items-center justify-center flex-shrink-0 mt-1">
                    <span class="text-xs font-bold text-blue-600">AI</span>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-3 py-2">
                    <div class="flex gap-1 items-center h-4">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                             style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                             style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                             style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Input --}}
        <div class="border-t border-gray-200 p-3">
            <div class="flex gap-2">
                <input
                    type="text"
                    x-model="question"
                    @keydown.enter="ask()"
                    placeholder="Typ een vraag..."
                    :disabled="loading"
                    class="flex-1 text-sm rounded-lg border-gray-300
                           focus:border-blue-500 focus:ring-blue-500
                           disabled:opacity-50 disabled:cursor-not-allowed"
                >
                <button
                    @click="ask()"
                    :disabled="loading || !question.trim()"
                    class="bg-blue-600 hover:bg-blue-700 text-white
                           rounded-lg px-3 py-2 transition-colors
                           disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2"
                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</div>

<script>
function chatbot() {
    return {
        open:             false,
        loading:          false,
        question:         '',
        messages:         [],
        showTicketButton: false,

        async ask() {
            if (!this.question.trim() || this.loading) return;

            const q       = this.question;
            this.question = '';
            this.loading  = true;
            this.showTicketButton = false;

            this.messages.push({
                id:      Date.now(),
                role:    'user',
                content: q,
            });

            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch('/chat/ask', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify({ question: q }),
                });

                const data = await response.json();

                this.messages.push({
                    id:      Date.now() + 1,
                    role:    'assistant',
                    content: data.answer,
                });

                if (data.answer.toLowerCase().includes('ticket')) {
                    this.showTicketButton = true;
                }

            } catch (e) {
                this.messages.push({
                    id:      Date.now() + 1,
                    role:    'assistant',
                    content: 'Er ging iets mis. Probeer het opnieuw.',
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        async resetChat() {
            await fetch('/chat/reset', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({}),
            });

            this.messages         = [];
            this.showTicketButton = false;
            this.question         = '';
        },

        scrollToBottom() {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        },

        renderMarkdown(msg) {
            if (msg.role === 'assistant') {
                return marked.parse(msg.content);
            }
            return msg.content;
        },
    }
}
</script>