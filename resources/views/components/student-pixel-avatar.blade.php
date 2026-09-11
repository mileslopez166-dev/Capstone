@props([
    'gender' => null,
    'name' => null,
    'size' => 'md',
    'showCard' => false,
])

@php
    $genderValue = strtolower((string) $gender);
    $isGirl = in_array($genderValue, ['female', 'girl'], true);
    $characterName = $isGirl ? 'Lyra Vale' : 'Nova Finch';
    $roleName = $isGirl ? 'Bright Ideas' : 'Neon Courier';
    $sizeClasses = match ($size) {
        'sm' => 'h-16 w-16',
        'lg' => 'h-36 w-36 sm:h-44 sm:w-44',
        default => 'h-24 w-24',
    };
    $cardClasses = $isGirl
        ? 'border-fuchsia-300/20 bg-[#211039] text-fuchsia-100 shadow-[0_24px_60px_rgba(78,27,124,0.28)]'
        : 'border-cyan-300/20 bg-[#101533] text-cyan-100 shadow-[0_24px_60px_rgba(15,35,88,0.28)]';
@endphp

<div {{ $attributes->merge(['class' => $showCard ? "overflow-hidden rounded-lg border p-4 {$cardClasses}" : '']) }}>
    @if ($showCard)
        <div class="mb-3 flex items-start justify-between gap-4">
            <div>
                <p class="font-label text-[9px] font-black uppercase tracking-[0.22em] {{ $isGirl ? 'text-fuchsia-300' : 'text-cyan-300' }}">Pixel Avatar</p>
                <p class="mt-1 font-headline text-xl font-black tracking-tight text-white">{{ $characterName }}</p>
            </div>
            <span class="rounded-sm border px-2 py-1 font-label text-[8px] font-black uppercase tracking-[0.18em] {{ $isGirl ? 'border-pink-400/40 text-pink-200' : 'border-cyan-400/40 text-cyan-200' }}">Ready</span>
        </div>
    @endif

    <div class="relative mx-auto {{ $sizeClasses }} image-render-pixel select-none" aria-label="{{ $characterName }} avatar" role="img" style="image-rendering: pixelated;">
        <div class="absolute inset-0 rounded-sm {{ $isGirl ? 'bg-purple-950' : 'bg-slate-950' }}"></div>
        <div class="absolute inset-[10%] rounded-sm border {{ $isGirl ? 'border-fuchsia-300/10 bg-[linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)]' : 'border-cyan-300/10 bg-[linear-gradient(rgba(255,255,255,0.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.035)_1px,transparent_1px)]' }} bg-[length:12px_12px]"></div>

        @if ($isGirl)
            <div class="absolute left-[24%] top-[21%] h-[49%] w-[54%] bg-[#21113f]"></div>
            <div class="absolute left-[19%] top-[27%] h-[42%] w-[12%] bg-[#120a2b]"></div>
            <div class="absolute right-[16%] top-[29%] h-[38%] w-[12%] bg-[#120a2b]"></div>
            <div class="absolute left-[31%] top-[31%] h-[26%] w-[39%] bg-[#ffad80]"></div>
            <div class="absolute left-[38%] top-[27%] h-[9%] w-[27%] bg-[#c26aa2]"></div>
            <div class="absolute left-[34%] top-[40%] h-[7%] w-[7%] bg-[#ffe777]"></div>
            <div class="absolute right-[29%] top-[40%] h-[7%] w-[7%] bg-[#ff5f9d]"></div>
            <div class="absolute left-[48%] top-[49%] h-[5%] w-[12%] bg-[#e85b63]"></div>
            <div class="absolute left-[26%] top-[59%] h-[21%] w-[49%] bg-[#1689c3]"></div>
            <div class="absolute left-[34%] top-[58%] h-[12%] w-[29%] bg-[#36c6d1]"></div>
            <div class="absolute left-[39%] top-[68%] h-[12%] w-[28%] bg-[#f36a9f]"></div>
            <div class="absolute right-[20%] top-[25%] h-[8%] w-[8%] bg-[#ffd86b]"></div>
        @else
            <div class="absolute left-[21%] top-[26%] h-[40%] w-[58%] bg-[#101533]"></div>
            <div class="absolute left-[28%] top-[29%] h-[18%] w-[45%] bg-[#3a3d6b]"></div>
            <div class="absolute left-[31%] top-[40%] h-[25%] w-[39%] bg-[#ffbd83]"></div>
            <div class="absolute left-[38%] top-[48%] h-[6%] w-[7%] bg-[#14213e]"></div>
            <div class="absolute right-[33%] top-[48%] h-[6%] w-[7%] bg-[#14213e]"></div>
            <div class="absolute left-[44%] top-[58%] h-[5%] w-[13%] bg-[#d55c5c]"></div>
            <div class="absolute left-[36%] top-[63%] h-[15%] w-[29%] bg-[#ff6961]"></div>
            <div class="absolute left-[26%] top-[70%] h-[16%] w-[50%] bg-[#12a7b5]"></div>
            <div class="absolute left-[36%] top-[77%] h-[7%] w-[16%] bg-[#ffe179]"></div>
        @endif
    </div>

    @if ($showCard)
        <div class="mt-4 grid grid-cols-2 gap-3 border-t pt-3 {{ $isGirl ? 'border-fuchsia-200/10' : 'border-cyan-200/10' }}">
            <div>
                <p class="font-label text-[8px] font-black uppercase tracking-[0.18em] {{ $isGirl ? 'text-fuchsia-300' : 'text-cyan-300' }}">Student</p>
                <p class="truncate text-sm font-bold text-white">{{ $name ?: 'Student' }}</p>
            </div>
            <div class="text-right">
                <p class="font-label text-[8px] font-black uppercase tracking-[0.18em] {{ $isGirl ? 'text-fuchsia-300' : 'text-cyan-300' }}">Style</p>
                <p class="text-sm font-bold text-white">{{ $roleName }}</p>
            </div>
        </div>
    @endif
</div>