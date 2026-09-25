<x-app-layout>
    @php
        $teacherName = $teacher->name;
        $teacherInitials = str($teacherName)->explode(' ')->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    @endphp

    <div class="teacher-ai-page min-h-screen">
        <x-teacher-sidebar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" active="ai-assistant" />

        <main class="min-h-screen lg:ml-72">
            <x-teacher-topbar :teacher-name="$teacherName" :teacher-initials="$teacherInitials" search-placeholder="Search teacher workspace..." />

            <div class="teacher-content">
                <header class="teacher-page-heading">
                    <div>
                        <div class="teacher-eyebrow"><span class="material-symbols-outlined" aria-hidden="true">psychology</span>Planning support</div>
                        <h1>Teachers AI Assistant</h1>
                        <p>Ask for help with assessment ideas, Phil-IRI interpretation, interventions, and AI-PGAALS workflows.</p>
                    </div>
                </header>

                @unless ($available)
                    <p class="teacher-ai-status" role="status">Teachers AI Assistant is not connected yet. Add the OpenAI API key first.</p>
                @endunless

                <section class="teacher-ai-assistant" x-data="teacherAiAssistant(@js($teacherAiConfig))">
                    <aside class="teacher-ai-prompts" aria-label="Suggested prompts">
                        <div>
                            <span class="material-symbols-outlined" aria-hidden="true">lightbulb</span>
                            <h2>Quick starts</h2>
                            <p>Use one, then edit it for your class.</p>
                        </div>
                        <template x-for="prompt in prompts" :key="prompt">
                            <button type="button" :disabled="!available || busy" @click="choosePrompt(prompt)" x-text="prompt"></button>
                        </template>
                    </aside>

                    <section class="teacher-ai-chat" aria-label="Teachers AI Assistant conversation">
                        <div class="teacher-ai-toolbar">
                            <div>
                                <h2>Classroom assistant</h2>
                                <p>Responses are drafts. Review before using with students.</p>
                            </div>
                            <span class="material-symbols-outlined" aria-hidden="true">auto_awesome</span>
                        </div>

                        <div class="teacher-ai-messages" x-ref="messages" role="log" aria-live="polite" tabindex="0">
                            <div class="teacher-ai-empty" x-show="turns.length === 0">
                                <span class="material-symbols-outlined" aria-hidden="true">forum</span>
                                <h3>What do you want to prepare?</h3>
                                <p>Ask for a reading passage, numeracy worksheet idea, intervention activity, or scoring explanation.</p>
                            </div>
                            <template x-for="turn in turns" :key="turn.id">
                                <article class="teacher-ai-turn">
                                    <div class="teacher-ai-question">
                                        <p>You</p>
                                        <strong x-text="turn.question"></strong>
                                    </div>
                                    <div class="teacher-ai-answer">
                                        <p>Teachers AI Assistant</p>
                                        <div x-text="turn.answer"></div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <p class="teacher-ai-status" role="status" x-show="busy" x-cloak>Preparing a response...</p>
                        <p class="teacher-ai-error" role="alert" x-show="error" x-text="error" x-cloak></p>

                        <form class="teacher-ai-compose" @submit.prevent="send()" :aria-busy="busy">
                            <label for="teacher-ai-question">Ask Teachers AI Assistant</label>
                            <textarea id="teacher-ai-question" x-ref="question" x-model="draft" :disabled="!available || busy" maxlength="2000" rows="4" placeholder="Example: Create a short Grade 6 story with questions about cause and effect." required></textarea>
                            <div>
                                <small x-text="draft.length + ' / 2000'"></small>
                                <button type="submit" :disabled="!available || busy || !draft.trim()">
                                    <span>Send</span>
                                    <span class="material-symbols-outlined" aria-hidden="true">send</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </section>

                <p class="teacher-ai-disclosure">Do not include student passwords, contact details, or unnecessary personal data. Questions are sent to OpenAI when the assistant is connected.</p>
                <noscript><p class="teacher-ai-error">JavaScript is required for Teachers AI Assistant.</p></noscript>
            </div>
        </main>
    </div>
</x-app-layout>
