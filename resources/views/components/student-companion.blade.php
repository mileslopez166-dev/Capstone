@props(['gender' => null, 'page' => 'home', 'placement' => 'sidebar', 'user' => null])

@php
    $isGirl = $user?->avatar_config
        ? \App\Support\AvatarWardrobe::resolve($user->avatar_config, $user->gender)['character'] === 'lyra'
        : in_array(strtolower((string) ($user?->gender ?? $gender)), ['female', 'girl'], true);
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
    $messages = ["What's the problem? Don't hesitate to ask for help."];
    $requestedSubject = request('subject');
    $tutorConfig = [
        'available' => (bool) config('tutor.enabled') && filled(config('tutor.key')),
        'sendUrl' => route('student.tutor.send'),
        'tutorUrl' => route('student.tutor.index'),
        'subject' => in_array($requestedSubject, ['literacy', 'numeracy'], true) ? $requestedSubject : 'literacy',
    ];
@endphp

<div class="campus-companion {{ $placement === 'mobile' ? 'campus-companion-mobile' : 'campus-companion-sidebar' }}"
    x-data="studentCompanion(@js($messages), @js($tutorConfig))"
    :class="{ 'is-speaking': isSpeaking, 'is-paused': isPaused || reducedMotion, 'is-compact': isMobile && compact, 'has-chat-open': chatOpen }">
    @if ($placement === 'mobile')
        <button type="button" class="companion-size-toggle" @click="toggleCompact()" :aria-expanded="(!compact).toString()" aria-label="Expand or collapse companion" title="Expand or collapse companion" data-no-click-sound><span class="material-symbols-outlined" aria-hidden="true" x-text="compact ? 'expand_more' : 'expand_less'">expand_more</span></button>
    @endif
    <div class="campus-speech">
        <div class="campus-speech-message">
            <span class="sr-only" x-text="message">{{ $messages[0] }}</span>
            <span class="campus-speech-copy" aria-hidden="true" x-text="displayText">{{ $messages[0] }}</span>
            <span class="campus-speech-cursor" aria-hidden="true"></span>
        </div>
        <button class="campus-avatar-chat-prompt" type="button" @click="openTutorPanel()" :disabled="!tutorAvailable" data-no-click-sound>
            <span class="material-symbols-outlined" aria-hidden="true">chat_bubble</span>
            <span>Ask a question</span>
        </button>
    </div>

    <div class="campus-avatar-chat-shell" x-show="chatOpen" x-cloak @keydown.escape.window="closeTutorPanel()">
        <section class="campus-avatar-chat-panel" role="dialog" aria-modal="false" aria-labelledby="avatar-chat-title-{{ $placement }}">
            <div class="campus-avatar-chat-head">
                <div>
                    <strong id="avatar-chat-title-{{ $placement }}">Ask Tutor</strong>
                    <span>What do you need help with?</span>
                </div>
                <button class="campus-avatar-chat-new" type="button" x-show="tutorChatId" @click="newTutorChat()" aria-label="Start a new tutor chat" title="Start a new tutor chat" data-no-click-sound>
                    <span class="material-symbols-outlined" aria-hidden="true">add</span>
                </button>
                <button class="campus-avatar-chat-close" type="button" @click="closeTutorPanel()" aria-label="Close tutor chat" title="Close tutor chat" data-no-click-sound>
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>
            <div class="campus-avatar-chat-log" x-ref="avatarChatLog" x-show="tutorTurns.length || pendingQuestion" role="log" aria-live="polite">
                <template x-for="turn in tutorTurns" :key="turn.id">
                    <div class="campus-avatar-chat-turn">
                        <p class="campus-avatar-chat-bubble is-student" x-text="turn.question"></p>
                        <p class="campus-avatar-chat-bubble is-tutor" x-text="turn.answer"></p>
                    </div>
                </template>
                <div class="campus-avatar-chat-turn" x-show="pendingQuestion">
                    <p class="campus-avatar-chat-bubble is-student" x-text="pendingQuestion"></p>
                    <p class="campus-avatar-chat-bubble is-tutor">Thinking...</p>
                </div>
            </div>
            <p class="campus-avatar-chat-empty" x-show="!tutorTurns.length && !pendingQuestion">What's the problem? Don't hesitate to ask for help.</p>
            <form class="campus-quick-tutor" @submit.prevent="askTutor()">
                <label class="sr-only" for="quick-tutor-{{ $placement }}">Ask the tutor</label>
                <div class="campus-quick-tutor-row">
                    <select x-model="quickSubject" aria-label="Question subject" :disabled="tutorBusy || tutorChatId">
                        <option value="literacy">Literacy</option>
                        <option value="numeracy">Numeracy</option>
                    </select>
                    <input id="quick-tutor-{{ $placement }}" x-ref="quickQuestion" x-model="quickQuestion" maxlength="1500" placeholder="Type your question..." type="text" :disabled="!tutorAvailable || tutorBusy" @focus="stop()">
                    <button type="submit" :disabled="!canAskTutor" aria-label="Send question to tutor" title="Send question to tutor" data-no-click-sound>
                        <span class="material-symbols-outlined" aria-hidden="true" x-text="tutorBusy ? 'hourglass_top' : 'send'">send</span>
                    </button>
                </div>
                <p class="campus-quick-tutor-status" x-show="quickTutorStatus" x-text="quickTutorStatus"></p>
            </form>
        </section>
    </div>

    <button class="campus-companion-character" type="button" @click="openTutorPanel()"
        aria-label="Open tutor chat with {{ $characterName }}" title="Ask {{ $characterName }} for help" data-no-click-sound>
        <x-student-character :user="$user" :gender="$gender" />
    </button>
</div>
