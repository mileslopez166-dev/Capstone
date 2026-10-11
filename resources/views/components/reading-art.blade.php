@props(['name'])
@php
    // Viewports into the supplied mockup preserve its original pixel artwork.
    $meadow = [
        'banner' => [232, 40, 480, 106],
        'book' => [74, 398, 116, 102],
        'books' => [758, 382, 118, 114],
        'clock' => [50, 1230, 111, 112],
        'island' => [807, 183, 134, 157],
        'read-sign' => [17, 226, 164, 154],
        'steps-sign' => [782, 983, 148, 196],
        'reader' => [29, 1477, 129, 146],
        'books-sign' => [779, 1476, 154, 190],
    ][$name];
    $space = [
        'banner' => [144, 47, 308, 42],
        'book' => [80, 357, 94, 94],
        'books' => [759, 323, 119, 121],
        'clock' => [75, 1164, 50, 51],
        'island' => [804, 1171, 102, 84],
        'read-sign' => [0, 123, 309, 195],
        'steps-sign' => [231, 1087, 478, 51],
        'reader' => [11, 1478, 125, 178],
        'books-sign' => [805, 1470, 126, 190],
    ][$name];
@endphp
<span {{ $attributes->class(['reading-art', 'reading-art-'.$name]) }} aria-hidden="true">
    @foreach (['meadow' => $meadow, 'space' => $space] as $world => [$x, $y, $width, $height])
        <span class="reading-art-variant" data-art-world="{{ $world }}" style="aspect-ratio:{{ $width }}/{{ $height }}">
            <img src="{{ asset('images/reading/'.($world === 'space' ? 'space-reference.png' : 'reference-art.png')) }}" alt="" draggable="false"
                style="width:{{ 941 / $width * 100 }}%;left:{{ -$x / $width * 100 }}%;top:{{ -$y / $height * 100 }}%;" />
        </span>
    @endforeach
</span>
