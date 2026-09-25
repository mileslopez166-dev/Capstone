<x-app-layout>
    <div class="tutor-page min-h-screen overflow-x-hidden bg-background font-body text-on-surface">
        <x-student-nav active="tutor" />
        <main class="min-h-screen px-4 py-8 pb-32 sm:px-8 lg:ml-72 lg:px-12">
            @php
                $tutorConfig = [
                    'available' => $available, 'chatId' => $current?->id, 'submissionId' => $submission?->id,
                    'subject' => $subject, 'title' => $current?->title() ?? $submission?->assessment?->title ?? 'New conversation',
                    'turns' => $turns->toArray(),
                    'chats' => $chats->map(fn ($chat) => ['id' => $chat->id, 'title' => $chat->title(), 'url' => route('student.tutor.index', $chat->id)])->values()->all(),
                    'sendUrl' => route('student.tutor.send'),
                    'deleteUrl' => $current ? route('student.tutor.destroy', $current->id) : null,
                    'helpUrl' => $current ? route('student.tutor.help', $current->id) : null,
                ];
            @endphp
            <div class="student-tutor" x-data="studentTutor(@js($tutorConfig))">
                <header class="tutor-heading">
                    <div><h1>Ask Tutor</h1><p>Your AI study space</p></div>
                    <a class="tutor-icon" href="{{ route('student.activities') }}" title="Back to activities" aria-label="Back to activities"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span></a>
                </header>
                @unless ($available)
                    <p class="tutor-status mb-4" role="status">The AI tutor is not connected yet. Your teacher can help you for now.</p>
                @endunless
                <div class="tutor-workspace">
                    <aside class="tutor-history" aria-label="Your conversations">
                        <a class="tutor-send" href="{{ route('student.tutor.index') }}"><span class="material-symbols-outlined" aria-hidden="true">add</span>New chat</a>
                        <h2>Your conversations</h2>
                        <p class="text-sm text-on-surface-variant" x-show="chats.length === 0">No conversations yet</p>
                        <nav aria-label="Chat history">
                            <template x-for="chat in chats" :key="chat.id">
                                <a class="tutor-history-link" :href="chat.url" :aria-current="chat.id === chatId ? 'page' : null"><span class="material-symbols-outlined" aria-hidden="true">chat_bubble_outline</span><span x-text="chat.title"></span></a>
                            </template>
                        </nav>
                        <div class="mt-4">{{ $chats->links() }}</div>
                    </aside>
                    <section class="tutor-chat" aria-label="AI study conversation">
                        <div class="tutor-toolbar">
                            <div><h2 x-text="title"></h2><p>{{ $submission ? 'Completed assessment review' : 'Grade 6 study questions' }}</p></div>
                            <div class="tutor-tools">
                                <details class="tutor-reading">
                                    <summary class="tutor-icon" title="Reading size" aria-label="Reading size"><span class="material-symbols-outlined" aria-hidden="true">text_fields</span></summary>
                                    <label>Text size <output x-text="textSize + ' px'"></output><input type="range" min="16" max="24" step="1" x-model="textSize" aria-label="Conversation text size"></label>
                                </details>
                                <button class="tutor-icon" type="button" title="Notify my teacher that I need help (no chat shared)" aria-label="Ask my teacher for help" :disabled="!chatId || busy" @click="askTeacher()"><span class="material-symbols-outlined" aria-hidden="true">person_raised_hand</span></button>
                                <button class="tutor-icon" type="button" title="Delete this conversation" aria-label="Delete this conversation" :disabled="!chatId || busy" @click="confirmDelete = !confirmDelete"><span class="material-symbols-outlined" aria-hidden="true">delete</span></button>
                            </div>
                            <div class="tutor-subject" role="group" aria-label="Subject">
                                <button type="button" :aria-pressed="subject === 'literacy'" :disabled="!!chatId || !!submissionId || busy" @click="subject = 'literacy'">Literacy</button>
                                <button type="button" :aria-pressed="subject === 'numeracy'" :disabled="!!chatId || !!submissionId || busy" @click="subject = 'numeracy'">Numeracy</button>
                            </div>
                        </div>
                        <div class="tutor-delete" x-show="confirmDelete" x-cloak>
                            <span>Delete this conversation permanently?</span>
                            <button class="tutor-send" type="button" :disabled="busy" @click="deleteChat()">Delete</button>
                            <button type="button" :disabled="busy" @click="confirmDelete = false">Cancel</button>
                        </div>
                        <div class="tutor-messages" x-ref="messages" role="log" aria-label="Messages" aria-live="polite" tabindex="0" :style="{ '--tutor-text-size': textSize + 'px' }">
                            <div class="tutor-empty" x-show="turns.length === 0">
                                <x-student-character :user="$student" />
                                <h3>What are you curious about?</h3>
                                <div class="tutor-prompts">
                                    <button type="button" :disabled="!available" @click="choosePrompt(subject === 'literacy' ? 'How can I find the main idea of a story?' : 'Can you explain equivalent fractions?')" x-text="subject === 'literacy' ? 'Finding the main idea' : 'Understanding fractions'"></button>
                                    <button type="button" :disabled="!available" @click="choosePrompt(subject === 'literacy' ? 'How can I understand a word I have not seen before?' : 'How do I know which operation to use in a word problem?')" x-text="subject === 'literacy' ? 'Understanding new words' : 'Solving word problems'"></button>
                                </div>
                            </div>
                            <template x-for="turn in turns" :key="turn.id">
                                <article class="tutor-turn">
                                    <div class="tutor-question"><p class="tutor-message-label">You</p><p class="tutor-message" x-text="turn.question"></p></div>
                                    <div class="tutor-answer"><p class="tutor-message-label">AI Tutor</p><p class="tutor-message" x-text="turn.answer"></p></div>
                                </article>
                            </template>
                        </div>
                        <p class="tutor-status" role="status" x-show="busy" x-cloak>Working on your request...</p>
                        <p class="tutor-error" role="alert" x-show="error" x-text="error" x-cloak></p>
                        <p class="tutor-notice" role="status" x-show="notice" x-text="notice" x-cloak></p>
                        <form class="tutor-compose" @submit.prevent="send()" :aria-busy="busy">
                            <label for="tutor-question">Your question</label>
                            <textarea id="tutor-question" x-ref="question" x-model="draft" :disabled="!available || busy" maxlength="1500" rows="3" placeholder="What part would you like to understand?" required></textarea>
                            <div class="tutor-compose-actions"><small x-text="draft.length + ' / 1500'"></small><button class="tutor-send" type="submit" :disabled="!available || busy || !draft.trim()"><span>Send</span><span class="material-symbols-outlined" aria-hidden="true">send</span></button></div>
                        </form>
                    </section>
                </div>
                <p class="tutor-disclosure">AI can make mistakes. Check important answers with your teacher. Questions and any linked learning material are sent to OpenAI. Chats are saved privately in your school account until deleted. Do not share names, contact details, passwords, or other personal information.</p>
                <noscript><p class="tutor-error">JavaScript is required for Ask Tutor.</p></noscript>
            </div>
        </main>
    </div>
</x-app-layout>
