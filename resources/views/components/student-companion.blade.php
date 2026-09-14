@props(['gender' => null, 'page' => 'home', 'placement' => 'sidebar'])

@php
    $characterName = in_array(strtolower((string) $gender), ['female', 'girl'], true) ? 'Lyra Vale' : 'Nova Finch';
    $messages = match ($page) {
        'activities' => ["Take your time. You've got this!", 'Every question is a chance to grow.', "Keep going! You're doing great!"],
        'leaderboard' => ['Your best is getting better!', 'Small steps make big progress!', 'Keep learning. Every point counts!'],
        'rewards' => ["Look at how far you've come!", 'Every little win is worth a smile!', 'Ready for your next adventure?'],
        'profile' => ["Hey! It's great to see you!", "Let's make today a good day!", 'A little practice goes a long way!'],
        default => ['Hey there! Ready for an adventure?', 'One small step, one big win!', "Keep going! You're doing great!"],
    };
@endphp

<div class="campus-companion {{ $placement === 'mobile' ? 'campus-companion-mobile' : 'campus-companion-sidebar' }}"
    x-data="studentCompanion(@js($messages))"
    :class="{ 'is-speaking': isSpeaking, 'is-paused': !canAnimate }">
    <div class="campus-speech">
        <span class="sr-only" x-text="message">{{ $messages[0] }}</span>
        <span class="campus-speech-copy" aria-hidden="true" x-text="displayText">{{ $messages[0] }}</span>
        <span class="campus-speech-cursor" aria-hidden="true"></span>
        <button class="campus-speech-pause" type="button" @click="togglePause()"
            :aria-pressed="isPaused.toString()"
            :aria-label="isPaused ? 'Resume avatar animation' : 'Pause avatar animation'"
            :title="isPaused ? 'Resume avatar animation' : 'Pause avatar animation'"
            x-show="!reducedMotion" data-no-click-sound>
            <span class="material-symbols-outlined" aria-hidden="true" x-text="isPaused ? 'play_arrow' : 'pause'">pause</span>
        </button>
    </div>
    <button class="campus-companion-character" type="button" @click="nextMessage()"
        aria-label="Next message from {{ $characterName }}" title="Next message from {{ $characterName }}" data-no-click-sound>
        <x-student-character :gender="$gender" />
    </button>
</div>
