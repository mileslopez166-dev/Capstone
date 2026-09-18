@props(['definition', 'itemKey', 'pageIndex', 'readonly' => true])
<fieldset class="worksheet-item-response" data-item-response="{{ $itemKey }}" aria-label="Answers for item {{ $definition['label'] }}">
    <legend class="sr-only">Item {{ $definition['label'] }}</legend>
    @foreach ($definition['fields'] as $field)
        @php $id = 'response-'.$pageIndex.'-'.$itemKey.'-'.$field['key']; @endphp
        @if ($field['type'] === 'temperature')
            @continue
        @elseif (in_array($field['type'], ['checkbox', 'radio']))
            <fieldset class="worksheet-choice-field"><legend>{{ $field['label'] }}</legend>
                <div class="worksheet-response-options">
                    @foreach ($field['options'] as $option)
                        <label><input type="{{ $field['type'] }}" name="{{ $id }}" value="{{ $option }}" data-response-field="{{ $field['key'] }}" @disabled($readonly)><span>{{ $option }}</span></label>
                    @endforeach
                </div>
            </fieldset>
        @elseif ($field['type'] === 'drawing')
            <div class="worksheet-item-drawing" data-item-drawing="{{ $field['key'] }}">
                <div class="worksheet-item-drawing-tools"><strong>{{ $field['label'] }}</strong>
                    @unless ($readonly)
                        <button type="button" class="worksheet-icon" data-item-undo title="Undo last stroke" aria-label="Undo drawing for item {{ $definition['label'] }}"><span class="material-symbols-outlined" aria-hidden="true">undo</span></button>
                        <button type="button" class="worksheet-icon" data-item-clear title="Clear drawing" aria-label="Clear drawing for item {{ $definition['label'] }}"><span class="material-symbols-outlined" aria-hidden="true">ink_eraser</span></button>
                    @endunless
                </div>
                <canvas width="600" height="360" data-item-canvas role="img" aria-label="Drawing for item {{ $definition['label'] }}"></canvas>
            </div>
        @else
            <label class="worksheet-response-field {{ $field['type'] === 'textarea' ? 'worksheet-field-wide' : '' }}" for="{{ $id }}">
                <span>{{ $field['label'] }}</span>
                @if ($field['type'] === 'textarea')
                    <textarea id="{{ $id }}" data-response-field="{{ $field['key'] }}" rows="3" maxlength="2000" @disabled($readonly)></textarea>
                @elseif ($field['type'] === 'select')
                    <select id="{{ $id }}" data-response-field="{{ $field['key'] }}" @disabled($readonly)><option value="">Select</option>@foreach ($field['options'] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>
                @else
                    <input id="{{ $id }}" data-response-field="{{ $field['key'] }}" type="text" inputmode="{{ in_array($field['type'], ['digit', 'integer']) ? 'numeric' : ($field['type'] === 'number' ? 'decimal' : 'text') }}" maxlength="{{ $field['type'] === 'digit' ? 1 : 200 }}" autocomplete="off" @disabled($readonly)>
                @endif
            </label>
        @endif
    @endforeach
</fieldset>
