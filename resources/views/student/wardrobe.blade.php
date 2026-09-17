<x-app-layout>
    <x-student-nav active="profile" />

    @php
        $oldAppearance = old('avatar');
        $initial = \App\Support\AvatarWardrobe::resolve(is_array($oldAppearance) ? $oldAppearance : $appearance, $student->gender);
        $tabs = ['character' => ['Character', 'face'], 'clothes' => ['Clothes', 'apparel'], 'accessories' => ['Accessories', 'headphones']];
    @endphp

    <main class="wardrobe-page lg:ml-72">
        <header class="wardrobe-heading">
            <div>
                <a class="wardrobe-back" href="{{ route('profile.edit') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span> Profile</a>
                <h1>My Wardrobe</h1>
            </div>
            <a class="wardrobe-owner" href="{{ route('student.practice.index') }}"><span class="material-symbols-outlined" aria-hidden="true">toll</span> {{ $coinBalance }} practice coins</a>
        </header>

        @if (session('status') === 'avatar-saved')
            <p class="wardrobe-notice" role="status"><span class="material-symbols-outlined" aria-hidden="true">check_circle</span> Your avatar is saved.</p>
        @endif
        @if (session('status') === 'wardrobe-unlocked')
            <p class="wardrobe-notice" role="status"><span class="material-symbols-outlined" aria-hidden="true">lock_open</span> New item unlocked! Ready to wear.</p>
        @endif
        @if ($errors->any())
            <div class="wardrobe-errors" role="alert">{{ $errors->first() }}</div>
        @endif

        <form id="wardrobe-purchase" method="POST" action="{{ route('student.wardrobe.purchase') }}">@csrf</form>
        <form method="POST" action="{{ route('student.wardrobe.update') }}"
            x-data="avatarWardrobe(@js($initial), @js($options))" @submit="saving = true">
            @csrf
            @method('PATCH')
            <div class="wardrobe-workspace">
                <section class="wardrobe-preview" :class="{ 'is-expanded': expanded }" aria-label="Avatar preview">
                    <div class="wardrobe-preview-heading">
                        <span>YOUR CHARACTER</span>
                        <div class="wardrobe-tools">
                            <button class="wardrobe-preview-toggle" type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-controls="wardrobe-avatar-stage" aria-label="Expand or collapse avatar preview" title="Expand or collapse preview"><span class="material-symbols-outlined" aria-hidden="true" x-text="expanded ? 'unfold_less' : 'unfold_more'">unfold_more</span></button>
                            <button type="button" @click="randomize()" title="Surprise me" aria-label="Randomize avatar"><span class="material-symbols-outlined" aria-hidden="true">casino</span></button>
                            <button type="button" @click="reset()" title="Undo changes" aria-label="Undo avatar changes" :disabled="!dirty"><span class="material-symbols-outlined" aria-hidden="true">undo</span></button>
                        </div>
                    </div>
                    <div class="wardrobe-stage" id="wardrobe-avatar-stage" x-ref="preview">
                        <div class="wardrobe-podium" aria-hidden="true"></div>
                        <x-wardrobe-character :appearance="$initial" />
                    </div>
                    <div class="wardrobe-character-name">
                        <h2 x-text="appearance.character === 'lyra' ? 'Lyra Vale' : 'Nova Finch'">{{ $initial['character'] === 'lyra' ? 'Lyra Vale' : 'Nova Finch' }}</h2>
                        <p x-text="catalog.outfit.items[look.outfit].label + ' / ' + catalog.color.items[look.color].label"></p>
                    </div>
                    <div class="wardrobe-preview-note" x-show="preview" x-cloak role="status"><span>Preview only</span><button type="button" class="ui-icon-button" @click="preview = null" aria-label="Clear item preview" title="Clear item preview"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></div>
                </section>

                <section class="wardrobe-editor" aria-label="Customize avatar">
                    <div class="wardrobe-tabs" role="tablist" aria-label="Wardrobe categories">
                        @foreach ($tabs as $tab => [$label, $icon])
                            <button type="button" id="wardrobe-tab-{{ $tab }}" role="tab" aria-controls="wardrobe-panel-{{ $tab }}"
                                :aria-selected="(tab === '{{ $tab }}').toString()" :tabindex="tab === '{{ $tab }}' ? 0 : -1"
                                @click="tab = '{{ $tab }}'" @keydown.arrow-right.prevent="moveTab(1)" @keydown.arrow-left.prevent="moveTab(-1)"
                                @keydown.home.prevent="selectTab('character')" @keydown.end.prevent="selectTab('accessories')">
                                <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span><span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="wardrobe-collections" role="group" aria-label="Outfit collections" x-show="tab !== 'character'" x-cloak>
                        @foreach (['all' => 'All', 'nova' => 'Boys', 'lyra' => 'Girls'] as $collection => $label)
                            <button type="button" @click="collection = '{{ $collection }}'" :aria-pressed="(collection === '{{ $collection }}').toString()">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="wardrobe-collections wardrobe-ownership" role="group" aria-label="Item ownership" x-show="tab !== 'character'" x-cloak>
                        @foreach (['all' => 'All', 'owned' => 'Owned', 'locked' => 'Locked'] as $filter => $label)
                            <button type="button" @click="ownership = '{{ $filter }}'" :aria-pressed="(ownership === '{{ $filter }}').toString()">{{ $label }}</button>
                        @endforeach
                    </div>
                    <p class="wardrobe-empty" x-show="!hasVisibleItems()" x-cloak>No items in this selection.</p>
                    @foreach ($tabs as $tab => [$label, $icon])
                        <div class="wardrobe-panel" id="wardrobe-panel-{{ $tab }}" role="tabpanel" aria-labelledby="wardrobe-tab-{{ $tab }}"
                            x-show="tab === '{{ $tab }}'" @if ($tab !== 'character') x-cloak @endif>
                            @foreach ($options as $key => $option)
                                @continue($option['tab'] !== $tab)
                                @php $isSwatch = isset($option['items'][array_key_first($option['items'])]['color']); @endphp
                                <fieldset class="wardrobe-fieldset" x-show="visibleField('{{ $key }}')">
                                    <legend>{{ $option['label'] }}</legend>
                                    <div class="{{ $isSwatch ? 'wardrobe-swatches' : 'wardrobe-items' }}">
                                        @foreach ($option['items'] as $value => $item)
                                            @php $locked = $item['locked'] ?? false; @endphp
                                            @if (!$isSwatch)<div class="wardrobe-choice {{ $locked ? 'is-locked' : '' }}" x-show="visibleItem('{{ $key }}', '{{ $value }}')" @if (isset($item['collection']) && $item['collection'] !== $initial['character']) x-cloak @endif>@endif
                                            <label class="{{ $isSwatch ? 'wardrobe-swatch' : 'wardrobe-item' }}" title="{{ $item['label'] }}">
                                                <input type="radio" class="sr-only" name="avatar[{{ $key }}]" value="{{ $value }}"
                                                    x-model="appearance.{{ $key }}" @checked($initial[$key] === $value) @disabled($locked)>
                                                @if ($isSwatch)
                                                    <span class="wardrobe-swatch-color" style="--swatch: {{ $item['color'] }}"><span class="material-symbols-outlined" aria-hidden="true">check</span></span>
                                                    <span class="sr-only">{{ $item['label'] }}</span>
                                                @else
                                                    <span class="wardrobe-item-body">
                                                        <span class="wardrobe-item-art">
                                                            @if ($value === 'none')
                                                                <span class="material-symbols-outlined wardrobe-no-item" aria-hidden="true">block</span>
                                                            @else
                                                                <x-wardrobe-character :appearance="array_replace($initial, [$key => $value])" :crop="$key" :data-choice-key="$key" :data-choice-value="$value" aria-hidden="true" />
                                                            @endif
                                                        </span>
                                                        <strong>{{ $item['label'] }}</strong>
                                                        @if (isset($item['detail']))<small>{{ $item['detail'] }}</small>@endif
                                                        <span class="material-symbols-outlined wardrobe-item-check" aria-hidden="true">check_circle</span>
                                                    </span>
                                                @endif
                                            </label>
                                            @if ($locked)
                                                <button class="wardrobe-try-on" type="button" @click="tryOn('{{ $key }}', '{{ $value }}')" :aria-pressed="(preview?.{{ $key }} === '{{ $value }}').toString()" aria-label="Preview {{ $item['label'] }}"><span class="material-symbols-outlined" aria-hidden="true">visibility</span>Try on</button>
                                                <button class="wardrobe-unlock" type="submit" form="wardrobe-purchase" name="item" value="{{ $key.':'.$value }}"
                                                    @disabled($coinBalance < $item['cost']) aria-label="Unlock {{ $item['label'] }} for {{ $item['cost'] }} coins" title="{{ $coinBalance < $item['cost'] ? 'Earn more coins in Practice Missions' : 'Unlock '.$item['label'] }}">
                                                    <span class="material-symbols-outlined" aria-hidden="true">lock</span> {{ $item['cost'] }} coins
                                                </button>
                                            @elseif (isset($item['cost']))<span class="wardrobe-owned">Unlocked</span>@endif
                                            @if (!$isSwatch)</div>@endif
                                        @endforeach
                                    </div>
                                    <x-input-error :messages="$errors->get('avatar.'.$key)" class="mt-2 text-sm text-error" />
                                </fieldset>
                            @endforeach
                        </div>
                    @endforeach
                </section>
            </div>
            <footer class="wardrobe-savebar">
                <p role="status" x-text="preview ? 'Preview only. Unlock to wear.' : (saving ? 'Saving your avatar...' : (dirty ? 'Outfit changed' : 'Ready for your next adventure'))">Ready for your next adventure</p>
                <button class="wardrobe-save" type="submit" :disabled="saving || !!preview"><span class="material-symbols-outlined" aria-hidden="true">check</span><span x-text="saving ? 'Saving...' : 'Save Avatar'">Save Avatar</span></button>
            </footer>
        </form>
    </main>
</x-app-layout>
