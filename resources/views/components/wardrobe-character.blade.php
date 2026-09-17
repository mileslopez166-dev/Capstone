@props(['appearance' => null, 'crop' => 'full'])

@php
    $look = \App\Support\AvatarWardrobe::resolve($appearance);
    $catalog = \App\Support\AvatarWardrobe::options();
    $avatarId = 'wardrobe-'.\Illuminate\Support\Str::uuid();
    $viewBox = match ($crop) {
        'portrait', 'headwear', 'character' => '12 0 176 176',
        'outfit' => '34 126 134 94',
        'bottoms' => '44 196 116 66',
        'shoes' => '40 236 124 38',
        'accessory' => match ($look['accessory']) {
            'glasses' => '12 0 176 176',
            'explorer_watch' => '24 176 65 58',
            default => '28 125 148 100',
        },
        default => '0 0 200 280',
    };
    $palette = collect(['skin', 'hair', 'color'])->map(fn ($key) => '--avatar-'.$key.':'.$catalog[$key]['items'][$look[$key]]['color'])->implode(';');
@endphp

<svg {{ $attributes->class('wardrobe-character')->merge(['viewBox' => $viewBox, 'style' => $palette]) }}
    xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Customized student avatar" data-avatar-renderer
    data-character="{{ $look['character'] }}" data-outfit="{{ $look['outfit'] }}" data-headwear="{{ $look['headwear'] }}">
    <defs>
        <linearGradient id="{{ $avatarId }}-shade" x1="0" y1="0" x2="1" y2=".3">
            <stop stop-color="#fff" stop-opacity=".12" />
            <stop offset=".55" stop-color="#fff" stop-opacity="0" />
            <stop offset="1" stop-color="#18242c" stop-opacity=".15" />
        </linearGradient>
    </defs>
    <g stroke="none" stroke-linejoin="round" stroke-linecap="round">
        <ellipse cx="102" cy="266" rx="47" ry="6" fill="#18242c" opacity=".16" />

        <g data-avatar-part="character" data-avatar-value="lyra" style="display: {{ $look['character'] === 'lyra' ? 'inline' : 'none' }}">
            <path d="M44 69Q37 18 97 17Q158 11 158 66L158 129Q176 151 158 168Q155 181 142 170Q130 177 125 163L68 161Q52 175 42 159Q29 149 42 128Z" fill="var(--avatar-hair)" />
            <path d="M143 48Q158 65 148 110Q142 137 153 156Q159 164 150 170Q168 179 166 157Q175 145 158 129L158 66Z" fill="#152331" opacity=".13" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="backpack" style="display: {{ $look['accessory'] === 'backpack' ? 'inline' : 'none' }}">
            <rect x="119" y="130" width="46" height="80" rx="16" fill="#e9b94e" />
            <rect x="143" y="147" width="23" height="45" rx="9" fill="#cc9639" />
            <path d="M140 134V126Q140 119 150 123L153 130" fill="none" stroke="#b67d35" stroke-width="5" />
            <path d="M153 153L156 159L162 160L157 164L158 170L153 167L148 170L149 164L144 160L151 159Z" fill="#fff0ae" />
        </g>

        <path d="M68 196L65 251Q77 257 88 251L101 215L114 251Q126 257 139 251L135 195Z" fill="var(--avatar-skin)" />
        <g data-avatar-part="bottoms" data-avatar-value="jeans" style="display: {{ $look['bottoms'] === 'jeans' ? 'inline' : 'none' }}">
            <path d="M64 194L62 248Q75 252 91 248L102 217L112 248Q127 252 142 246L137 194Z" fill="#344b6b" />
            <path d="M102 198L102 217L112 248L124 250L114 207Z" fill="#182c49" opacity=".4" />
            <path d="M71 209L68 239M130 209L135 237" fill="none" stroke="#91a6bc" stroke-opacity=".55" stroke-width="1.3" stroke-dasharray="4 4" />
            <path d="M63 242L91 243L91 249L62 248ZM112 244L141 241L142 247L113 249Z" fill="#738ba6" />
        </g>
        <g data-avatar-part="bottoms" data-avatar-value="shorts" style="display: {{ $look['bottoms'] === 'shorts' ? 'inline' : 'none' }}">
            <path d="M66 194L61 226Q76 232 92 228L102 212L113 228Q129 232 143 225L137 194Z" fill="#53958d" />
            <path d="M102 201L102 212L113 228L126 230L116 207Z" fill="#163e48" opacity=".18" />
            <path d="M64 223Q77 227 90 224M115 224Q128 227 140 221" fill="none" stroke="#bddbcf" stroke-width="3" />
            <path d="M75 211L78 216L84 214" fill="none" stroke="#f7dc8f" stroke-width="3" />
        </g>

        <g data-avatar-part="bottoms" data-avatar-value="skirt" style="display: {{ $look['bottoms'] === 'skirt' ? 'inline' : 'none' }}">
            <path d="M66 195H135L151 234Q104 250 49 234Z" fill="var(--avatar-color)" />
            <path d="M72 200L65 235M90 201L87 240M113 201L118 240M130 201L139 235" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="3" />
            <path d="M52 232Q101 247 148 233" fill="none" stroke="#fff" stroke-opacity=".65" stroke-width="4" />
        </g>
        <g data-avatar-part="bottoms" data-avatar-value="cargo_pants" style="display: {{ $look['bottoms'] === 'cargo_pants' ? 'inline' : 'none' }}">
            <path d="M64 194L60 249H91L103 216L113 249H143L138 194Z" fill="#668b77" />
            <path d="M64 213H83V232H63ZM121 213H140V232H122Z" fill="#426957" />
            <path d="M63 213H84L81 218H65ZM120 213H141L138 218H123Z" fill="#92b399" />
            <path d="M102 205L113 249M64 244H90M114 244H141" fill="none" stroke="#b1c8a6" stroke-width="3" />
            <circle cx="73" cy="221" r="2" fill="#f4d792" /><circle cx="131" cy="221" r="2" fill="#f4d792" />
        </g>
        <g data-avatar-part="bottoms" data-avatar-value="pleated_skirt" style="display: {{ $look['bottoms'] === 'pleated_skirt' ? 'inline' : 'none' }}">
            <path d="M65 195H136L154 235Q103 251 47 235Z" fill="#b6567e" />
            <path d="M76 200L67 239M93 200L89 243M110 200L115 243M128 200L139 239" fill="none" stroke="#f3b2cd" stroke-width="5" />
            <path d="M50 233Q103 247 151 233" fill="none" stroke="#ffdfeb" stroke-width="3" />
            <path d="M117 203Q99 193 102 204Q104 214 117 208Q130 216 131 205Q133 194 117 203Z" fill="#fff2d1" />
            <circle cx="117" cy="206" r="3" fill="#e6b758" />
        </g>
        <g data-avatar-part="bottoms" data-avatar-value="tutu_skirt" style="display: {{ $look['bottoms'] === 'tutu_skirt' ? 'inline' : 'none' }}">
            <path d="M66 194H135L157 234Q103 256 44 234Z" fill="#c484bd" />
            <path d="M67 196H134L150 225Q141 235 130 229Q118 240 106 232Q92 240 80 231Q63 237 51 226Z" fill="#ebb9df" />
            <path d="M67 195H134L143 214Q132 225 122 219Q110 229 99 221Q87 228 77 219Q64 223 58 215Z" fill="#f7dcf0" />
            <path d="M78 202L74 224M124 203L130 226" stroke="#fff" stroke-opacity=".7" stroke-width="2" />
            <circle cx="93" cy="225" r="2" fill="#fff3ab" /><circle cx="115" cy="237" r="2" fill="#fff3ab" />
        </g>

        <g data-avatar-part="shoes" data-avatar-value="sneakers" style="display: {{ $look['shoes'] === 'sneakers' ? 'inline' : 'none' }}">
            <path d="M64 244L89 244L91 259Q73 269 46 260Q43 249 64 244ZM113 244L139 243Q158 248 162 257Q141 269 112 260Z" fill="var(--avatar-color)" />
            <path d="M47 255Q67 261 90 254L91 261Q68 268 45 261ZM112 255Q139 261 160 252L163 258Q141 269 111 262Z" fill="#f8f6f2" />
            <path d="M63 248L76 251M67 244L80 247M123 247L137 245M127 251L141 249" fill="none" stroke="#fff" stroke-width="2.5" />
            <path d="M52 252Q56 248 61 249M145 249L152 252" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="3" />
        </g>
        <g data-avatar-part="shoes" data-avatar-value="high_tops" style="display: {{ $look['shoes'] === 'high_tops' ? 'inline' : 'none' }}">
            <path d="M65 234Q77 230 89 235L91 260Q68 269 46 259Q46 250 63 246ZM112 235Q126 230 138 234L142 246Q157 248 162 258Q136 269 111 260Z" fill="var(--avatar-color)" />
            <path d="M66 235L86 236M115 236L135 235" fill="none" stroke="#fff" stroke-width="4" />
            <path d="M47 254Q68 260 91 254L91 261Q67 268 46 261ZM112 254Q136 260 160 253L163 259Q137 268 111 261Z" fill="#f8f6f2" />
            <path d="M71 242H82M69 248H80M120 242H131M122 248H133" fill="none" stroke="#fff" stroke-width="2.5" />
        </g>
        <g data-avatar-part="shoes" data-avatar-value="boots" style="display: {{ $look['shoes'] === 'boots' ? 'inline' : 'none' }}">
            <path d="M64 234Q77 230 89 235L91 260Q68 268 46 259Q46 249 63 246ZM112 235Q125 230 139 234L142 246Q158 249 162 259Q136 268 111 260Z" fill="#bd894d" />
            <path d="M65 235H87M115 235H136" stroke="#e9cda3" stroke-width="5" />
            <path d="M47 257Q68 263 91 257M113 258Q136 263 160 256" fill="none" stroke="#544741" stroke-width="6" />
            <path d="M71 241H82M69 247H80M120 241H132M122 247H133" fill="none" stroke="#f9ead1" stroke-width="2" />
        </g>

        <g data-avatar-part="shoes" data-avatar-value="comet_sneakers" style="display: {{ $look['shoes'] === 'comet_sneakers' ? 'inline' : 'none' }}">
            <path d="M64 240H88L92 259Q69 269 44 260Q44 249 64 244ZM114 240H138L141 245Q158 248 164 258Q141 269 111 260Z" fill="#354779" />
            <path d="M45 258Q65 263 91 257M113 258Q141 265 162 256" stroke="#6de6d0" stroke-width="6" fill="none" />
            <path d="M71 242L62 254H72L68 260L85 248H75L82 242ZM132 242L122 254H132L128 260L146 248H136L144 242Z" fill="#ffdd74" />
            <path d="M65 241L85 242M116 242H136" stroke="#fff" stroke-width="3" />
        </g>
        <g data-avatar-part="shoes" data-avatar-value="ribbon_flats" style="display: {{ $look['shoes'] === 'ribbon_flats' ? 'inline' : 'none' }}">
            <path d="M66 248Q77 255 88 247L91 260Q68 269 47 260Q48 251 66 248ZM113 247Q125 255 138 247Q155 250 160 259Q138 270 111 261Z" fill="#da7495" />
            <path d="M49 260Q71 266 90 260M113 261Q136 267 159 259" stroke="#993c69" stroke-width="3" fill="none" />
            <path d="M70 251Q56 240 55 249Q56 256 69 254Q81 258 84 251Q83 244 70 251ZM137 252Q124 242 123 250Q124 257 136 255Q150 258 151 250Q148 244 137 252Z" fill="#ffe5d7" />
            <circle cx="69" cy="253" r="2.5" fill="#f0bd65" /><circle cx="137" cy="254" r="2.5" fill="#f0bd65" />
        </g>

        <path d="M56 153Q44 153 43 166L36 198Q35 211 45 214Q56 218 60 205L68 174ZM140 153Q155 151 158 166L166 199Q169 212 157 215Q146 217 143 204L133 174Z" fill="var(--avatar-skin)" />
        <path d="M154 174L161 199Q165 210 157 215Q169 214 166 199L159 170Z" fill="#18242c" opacity=".09" />
        <path d="M72 129Q62 130 52 140L41 170Q49 179 64 177L69 164L64 195Q62 205 77 208H126Q142 205 138 195L134 164L141 180Q157 180 164 171L153 143Q142 130 127 129Z" fill="var(--avatar-color)" />
        <path d="M72 129Q62 130 52 140L41 170Q49 179 64 177L69 164L64 195Q62 205 77 208H126Q142 205 138 195L134 164L141 180Q157 180 164 171L153 143Q142 130 127 129Z" fill="url(#{{ $avatarId }}-shade)" />
        <path d="M69 160L64 177L63 167L65 153ZM128 144L134 164L138 196L126 204Z" fill="#132c42" opacity=".12" />
        <path d="M44 168Q53 173 64 172M141 174Q151 176 161 169M67 200Q101 205 135 199" fill="none" stroke="#fff" stroke-opacity=".24" stroke-width="3" />

        <g data-avatar-part="outfit" data-avatar-value="hoodie" style="display: {{ $look['outfit'] === 'hoodie' ? 'inline' : 'none' }}">
            <path d="M79 126Q69 127 67 136Q75 154 100 146Q125 154 135 136Q132 126 121 125Z" fill="var(--avatar-color)" />
            <path d="M81 133Q101 143 120 132" fill="none" stroke="#142d41" stroke-opacity=".18" stroke-width="5" />
            <path d="M88 146V160M114 146V160" stroke="#f8f8f4" stroke-width="2" />
            <path d="M83 176Q100 180 118 176L123 192Q101 198 79 192Z" fill="#fff" fill-opacity=".17" />
            <path d="M84 178L81 189M117 178L120 189" stroke="#21334a" stroke-opacity=".25" stroke-width="2" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="varsity" style="display: {{ $look['outfit'] === 'varsity' ? 'inline' : 'none' }}">
            <path d="M70 132Q60 132 52 143L41 170Q49 180 64 177L73 152ZM130 131Q144 132 153 144L164 171Q157 182 141 179L129 153Z" fill="#f4f0e6" />
            <path d="M78 129L101 139L122 127L127 134L102 149L73 136Z" fill="#ecede5" />
            <path d="M101 143L103 203" stroke="#f4f0e6" stroke-width="4" />
            <circle cx="97" cy="162" r="1.6" fill="#f5f5ef" /><circle cx="98" cy="178" r="1.6" fill="#f5f5ef" /><circle cx="99" cy="193" r="1.6" fill="#f5f5ef" />
            <path d="M120 155L123 161L130 162L125 167L126 174L120 170L114 174L115 167L110 162L117 161Z" fill="#f8d879" />
            <path d="M76 181L85 179M117 179L128 182" stroke="#193849" stroke-opacity=".3" stroke-width="3" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="explorer" style="display: {{ $look['outfit'] === 'explorer' ? 'inline' : 'none' }}">
            <path d="M80 130L70 135L65 200Q77 207 95 206V148ZM120 129L130 134L139 200Q122 207 108 206V148Z" fill="#ead29a" />
            <path d="M73 165H91V183Q82 188 72 183ZM113 165H130L132 183Q121 188 113 183Z" fill="#c8a76c" />
            <path d="M73 165L82 172L91 165M113 165L122 172L131 165" fill="#f4dfb5" />
            <path d="M81 131L93 143L88 153L74 136M118 131L109 143L115 153L128 135" fill="#f6e7bc" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="jersey" style="display: {{ $look['outfit'] === 'jersey' ? 'inline' : 'none' }}">
            <path d="M78 130Q101 149 123 129M45 162L65 169M139 168L160 160" fill="none" stroke="#f7f4e9" stroke-width="5" />
            <path d="M89 162H111L98 188" fill="none" stroke="#f7f4e9" stroke-width="7" />
            <path d="M66 197Q102 204 136 195" fill="none" stroke="#f7f4e9" stroke-width="3" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="bomber" style="display: {{ $look['outfit'] === 'bomber' ? 'inline' : 'none' }}">
            <path d="M75 130L53 141L36 196L59 204L69 161L65 204H138L134 162L145 204L169 197L153 142L126 130Z" fill="var(--avatar-color)" />
            <path d="M80 132L101 148L121 132L127 136L102 156L74 136Z" fill="#e9e4d4" />
            <path d="M101 152V202M38 191L59 198M145 197L166 190M68 199H135" stroke="#294c5d" stroke-width="6" fill="none" />
            <path d="M78 176L86 173M117 173L126 177" stroke="#d8f6ef" stroke-width="4" />
            <path d="M123 151L126 158L135 159L129 164L130 172L123 168L116 172L117 164L111 159L120 158Z" fill="#fbd884" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="captain_jacket" style="display: {{ $look['outfit'] === 'captain_jacket' ? 'inline' : 'none' }}">
            <path d="M77 130L51 143L37 197L59 202L71 159L64 205H139L132 159L146 202L168 195L153 142L126 129Z" fill="#315e76" />
            <path d="M79 130L101 143L124 130L101 181Z" fill="#eef6ed" />
            <path d="M73 132L93 145L86 158L100 180L70 155ZM128 132L110 145L117 158L101 180L135 154Z" fill="#5889a3" />
            <path d="M41 184L63 190M143 191L163 185M43 178L64 184M142 185L161 179" stroke="#f5cc65" stroke-width="3" />
            <circle cx="91" cy="185" r="3" fill="#f5cc65" /><circle cx="114" cy="185" r="3" fill="#f5cc65" /><circle cx="91" cy="197" r="3" fill="#f5cc65" /><circle cx="114" cy="197" r="3" fill="#f5cc65" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="space_suit" style="display: {{ $look['outfit'] === 'space_suit' ? 'inline' : 'none' }}">
            <path d="M77 128L51 140L35 197L59 203L70 160L65 205H138L133 159L145 202L169 196L153 139L125 128Z" fill="#e5edf0" />
            <path d="M77 128Q101 153 126 128M39 185L63 192M141 191L165 184M68 200H135" stroke="var(--avatar-color)" stroke-width="8" fill="none" />
            <rect x="80" y="156" width="43" height="30" rx="6" fill="#426278" />
            <rect x="87" y="162" width="29" height="8" rx="2" fill="#72e4cd" />
            <circle cx="90" cy="178" r="3" fill="#f4c25f" /><circle cx="102" cy="178" r="3" fill="#ed8195" /><circle cx="114" cy="178" r="3" fill="#cfe8f7" />
            <path d="M123 188Q135 187 135 171" stroke="#eda852" stroke-width="4" fill="none" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="racer_jacket" style="display: {{ $look['outfit'] === 'racer_jacket' ? 'inline' : 'none' }}">
            <path d="M77 130L52 141L36 196L59 203L69 160L65 205H138L133 160L145 203L168 196L153 141L125 130Z" fill="var(--avatar-color)" />
            <path d="M64 139L45 198M140 139L160 199" stroke="#ffecb3" stroke-width="7" />
            <path d="M79 133L101 145L123 132M69 201H134" fill="none" stroke="#283a52" stroke-width="6" />
            <path d="M101 147V204" stroke="#fff" stroke-width="3" />
            <rect x="76" y="157" width="19" height="18" rx="2" fill="#fff" />
            <path d="M76 157H82V163H76ZM88 157H94V163H88ZM82 163H88V169H82ZM76 169H82V175H76ZM88 169H94V175H88Z" fill="#273b52" />
            <path d="M118 157L112 171H122L116 182L132 166H122L127 157Z" fill="#ffe079" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="cardigan" style="display: {{ $look['outfit'] === 'cardigan' ? 'inline' : 'none' }}">
            <path d="M78 130L53 142L36 196L58 203L69 159L64 202Q80 215 99 204Q123 216 140 202L133 159L145 203L168 196L153 142L124 130Z" fill="var(--avatar-color)" />
            <path d="M80 129L101 146L122 129L101 184Z" fill="#fff2e2" />
            <path d="M78 130L101 183V207M123 130L102 182M39 191L58 197M145 197L165 191" stroke="#ffd5df" stroke-width="5" fill="none" />
            <circle cx="102" cy="185" r="2" fill="#bb6680" /><circle cx="102" cy="197" r="2" fill="#bb6680" />
            <path d="M120 162C109 151 107 168 120 175C133 168 131 151 120 162Z" fill="#ffe2eb" />
            <path d="M75 183Q82 190 90 183M113 185Q124 192 132 184" stroke="#fff" stroke-opacity=".45" stroke-width="3" fill="none" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="sailor_blouse" style="display: {{ $look['outfit'] === 'sailor_blouse' ? 'inline' : 'none' }}">
            <path d="M77 129L53 142L40 171Q50 181 65 175L71 160L65 204H138L133 160L140 177Q156 182 165 170L152 142L125 129Z" fill="#fff1e9" />
            <path d="M72 130L101 163L131 130L143 141L101 178L60 143Z" fill="var(--avatar-color)" />
            <path d="M69 139L101 169L135 139M45 169L62 173M142 173L161 168M68 199H135" stroke="#aeced9" stroke-width="3" fill="none" />
            <path d="M101 172Q82 158 82 170Q84 185 99 178L92 195L103 190L112 196L105 177Q121 183 123 170Q122 159 101 172Z" fill="#dd738d" />
        </g>
        <g data-avatar-part="outfit" data-avatar-value="starlight_top" style="display: {{ $look['outfit'] === 'starlight_top' ? 'inline' : 'none' }}">
            <path d="M77 130L55 138Q39 146 42 169Q53 183 67 174L71 160L65 203Q101 214 138 203L133 160L139 176Q157 183 165 168Q168 145 148 136L125 130Z" fill="var(--avatar-color)" />
            <path d="M80 130Q101 146 122 130M47 168L65 172M141 173L161 168M68 201Q101 209 135 201" stroke="#fff0cb" stroke-width="5" fill="none" />
            <path d="M101 155L107 168L122 170L110 180L113 194L101 187L88 194L91 180L80 170L95 168Z" fill="#ffe3a0" />
            <path d="M99 163L96 171L88 172" stroke="#fff" stroke-width="2" fill="none" />
            <circle cx="73" cy="156" r="2" fill="#fff" /><circle cx="130" cy="182" r="2" fill="#fff" />
        </g>

        <g data-avatar-part="accessory" data-avatar-value="backpack" style="display: {{ $look['accessory'] === 'backpack' ? 'inline' : 'none' }}">
            <path d="M74 134Q67 153 72 183M127 134Q138 155 133 183" fill="none" stroke="#c79337" stroke-width="7" />
            <path d="M70 162H76M130 162H136" stroke="#fff0ab" stroke-width="4" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="scarf" style="display: {{ $look['accessory'] === 'scarf' ? 'inline' : 'none' }}">
            <path d="M106 136L121 179L136 173L117 132Z" fill="#e4ad46" />
            <path d="M78 123Q101 133 124 120L128 134Q101 149 74 136Z" fill="#f3ca70" />
            <path d="M122 168L131 164" stroke="#fff1bd" stroke-width="3" />
        </g>

        <path d="M48 63Q46 26 97 25Q146 22 152 61L155 96Q157 126 102 127Q45 128 46 99Z" fill="var(--avatar-skin)" />
        <path d="M135 43Q148 66 145 99Q146 115 119 125Q156 123 155 96L152 61Z" fill="#563e35" opacity=".09" />
        <g data-avatar-part="character" data-avatar-value="nova" style="display: {{ $look['character'] === 'nova' ? 'inline' : 'none' }}">
            <path d="M47 85Q35 67 45 49Q38 34 55 28Q61 13 81 21Q99 9 113 22Q135 14 143 34Q161 43 153 70L145 87L140 62Q124 69 112 50Q95 74 77 61Q63 73 54 65Z" fill="var(--avatar-hair)" />
            <path d="M46 49Q65 34 79 40Q97 26 113 35Q130 30 142 42Q129 19 113 22Q99 9 81 21Q61 13 55 28Q38 34 46 49Z" fill="#fff" opacity=".13" />
            <path d="M140 62L145 87L153 70Q158 53 152 43Q153 62 140 62Z" fill="#122530" opacity=".15" />
        </g>
        <g data-avatar-part="character" data-avatar-value="lyra" style="display: {{ $look['character'] === 'lyra' ? 'inline' : 'none' }}">
            <path d="M46 84Q33 58 49 33Q64 11 103 18Q157 14 157 59L149 95Q138 87 139 70Q112 74 98 44Q82 73 62 69Q57 81 46 84Z" fill="var(--avatar-hair)" />
            <path d="M48 39Q62 17 102 22Q131 17 147 38Q124 49 99 38Q73 57 48 39Z" fill="#fff" opacity=".16" />
            <path d="M98 44Q104 66 128 72Q111 64 107 47Z" fill="#152a3b" opacity=".12" />
            <path d="M139 60Q129 50 127 58Q128 69 140 65Q151 69 153 58Q149 50 139 60Z" fill="var(--avatar-color)" />
            <circle cx="139" cy="62" r="3" fill="#f5d982" />
        </g>
        <g class="wardrobe-eyes" fill="#151a21">
            <ellipse cx="78" cy="96" rx="6.4" ry="9.5" transform="rotate(-5 78 96)" />
            <ellipse cx="117" cy="96" rx="6.4" ry="9.5" transform="rotate(5 117 96)" />
        </g>

        <g data-avatar-part="accessory" data-avatar-value="glasses" style="display: {{ $look['accessory'] === 'glasses' ? 'inline' : 'none' }}" fill="#fff" fill-opacity=".07" stroke="#384047" stroke-width="2.8">
            <circle cx="78" cy="96" r="17" /><circle cx="117" cy="96" r="17" />
            <path d="M95 93H100M61 92L48 88M134 92L148 88" fill="none" />
            <path d="M66 91Q67 83 75 82M105 91Q106 83 114 82" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="2" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="cap" style="display: {{ $look['headwear'] === 'cap' ? 'inline' : 'none' }}">
            <path d="M43 51Q42 16 94 15Q145 13 151 48Z" fill="var(--avatar-color)" />
            <path d="M94 15Q130 13 139 48L151 48Q145 13 94 15Z" fill="#163140" opacity=".16" />
            <path d="M71 48Q123 39 162 48Q176 54 166 60Q126 65 79 55Z" fill="var(--avatar-color)" />
            <path d="M79 55Q127 59 170 54Q174 63 139 63Q112 63 79 55Z" fill="#173345" opacity=".15" />
            <path d="M95 18L95 40" stroke="#fff" stroke-opacity=".23" stroke-width="1.6" />
            <rect x="59" y="31" width="15" height="11" rx="3" fill="#f3e7c7" />
            <circle cx="95" cy="15" r="4" fill="var(--avatar-color)" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="beanie" style="display: {{ $look['headwear'] === 'beanie' ? 'inline' : 'none' }}">
            <path d="M43 49Q42 9 99 10Q153 9 155 49Z" fill="var(--avatar-color)" />
            <path d="M121 13Q144 25 142 49H155Q154 18 121 13Z" fill="#183947" opacity=".14" />
            <path d="M42 42Q99 31 155 42L157 58Q99 47 40 59Z" fill="var(--avatar-color)" />
            <path d="M43 45Q100 35 155 45" fill="none" stroke="#fff" stroke-opacity=".23" stroke-width="5" />
            <path d="M57 46V55M70 44V53M83 42V51M111 42V51M124 44V53M137 46V55" stroke="#132b38" stroke-opacity=".16" stroke-width="1.5" />
            <rect x="92" y="40" width="16" height="16" rx="2" fill="#f6e6ba" />
            <path d="M97 48L100 51L104 45" fill="none" stroke="#ba8a47" stroke-width="1.8" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="headphones" style="display: {{ $look['headwear'] === 'headphones' ? 'inline' : 'none' }}">
            <path d="M40 90V58Q40 12 99 12Q159 12 159 58V90" fill="none" stroke="#263139" stroke-width="9" />
            <path d="M42 55Q45 17 99 17Q153 17 157 55" fill="none" stroke="var(--avatar-color)" stroke-width="5" />
            <rect x="34" y="78" width="17" height="31" rx="7" fill="var(--avatar-color)" /><rect x="150" y="78" width="17" height="31" rx="7" fill="var(--avatar-color)" />
            <path d="M39 84V101M155 84V101" stroke="#fff" stroke-opacity=".3" stroke-width="3" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="star_cap" style="display: {{ $look['headwear'] === 'star_cap' ? 'inline' : 'none' }}">
            <path d="M43 51Q42 16 94 15Q145 13 151 48Z" fill="#36aa95" />
            <path d="M74 47Q125 39 162 48Q181 60 157 63Q117 66 74 54Z" fill="#197e72" />
            <path d="M83 19L88 30L101 31L91 39L94 50L83 43L73 50L75 38L66 31L78 29Z" fill="#ffe391" />
            <path d="M111 20Q131 25 140 40" fill="none" stroke="#83dbc7" stroke-width="3" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="crown" style="display: {{ $look['headwear'] === 'crown' ? 'inline' : 'none' }}">
            <path d="M52 22L76 37L99 10L123 36L148 20L139 58Q100 65 60 57Z" fill="#f4bd46" />
            <path d="M57 45Q100 55 143 45L140 58Q100 66 60 57Z" fill="#e29432" />
            <path d="M99 29L106 39L99 49L92 39Z" fill="#3ac6c2" />
            <circle cx="52" cy="22" r="4" fill="#fff0ac" /><circle cx="99" cy="10" r="4" fill="#fff0ac" /><circle cx="148" cy="20" r="4" fill="#fff0ac" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="medal" style="display: {{ $look['accessory'] === 'medal' ? 'inline' : 'none' }}">
            <path d="M78 130L99 164L120 130" fill="none" stroke="#e26485" stroke-width="9" />
            <circle cx="99" cy="173" r="17" fill="#e8a62f" /><circle cx="99" cy="172" r="13" fill="#ffdc74" />
            <path d="M99 160L102 168L111 168L104 174L107 182L99 177L91 182L94 174L87 168L96 168Z" fill="#d99229" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="aviator_goggles" style="display: {{ $look['headwear'] === 'aviator_goggles' ? 'inline' : 'none' }}">
            <path d="M43 46Q99 28 151 46" fill="none" stroke="#664c43" stroke-width="12" />
            <path d="M86 43H111" stroke="#f4c978" stroke-width="7" />
            <rect x="52" y="28" width="37" height="29" rx="11" fill="#e9bc69" /><rect x="108" y="28" width="37" height="29" rx="11" fill="#e9bc69" />
            <rect x="57" y="33" width="27" height="19" rx="7" fill="#83d2df" /><rect x="113" y="33" width="27" height="19" rx="7" fill="#83d2df" />
            <path d="M61 44L71 36M117 44L127 36" stroke="#e4fffc" stroke-width="4" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="sailor_cap" style="display: {{ $look['headwear'] === 'sailor_cap' ? 'inline' : 'none' }}">
            <path d="M43 37Q43 10 101 12Q158 10 155 40L143 57H54Z" fill="#f6f1e5" />
            <path d="M47 40Q100 48 151 39L144 57Q101 64 53 57Z" fill="#375e7c" />
            <path d="M96 25V48M86 37Q84 49 96 50Q108 49 107 37M89 34H104" stroke="#f4cb70" stroke-width="3" fill="none" />
            <circle cx="96" cy="24" r="3" fill="none" stroke="#f4cb70" stroke-width="2" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="ribbon_bow" style="display: {{ $look['headwear'] === 'ribbon_bow' ? 'inline' : 'none' }}">
            <path d="M126 32Q101 6 100 26Q94 49 124 39Q150 59 160 39Q170 20 134 31Z" fill="#eb85a6" />
            <path d="M123 36L107 58L120 55L126 63L133 41L143 59L148 49L159 51L137 35Z" fill="#d96a90" />
            <path d="M121 31L110 23M139 33L153 33" stroke="#ffc4d9" stroke-width="4" />
            <rect x="124" y="27" width="12" height="17" rx="4" fill="#ffe1ac" transform="rotate(12 130 35)" />
        </g>
        <g data-avatar-part="headwear" data-avatar-value="flower_crown" style="display: {{ $look['headwear'] === 'flower_crown' ? 'inline' : 'none' }}">
            <path d="M43 47Q96 17 152 47" stroke="#4b9c77" stroke-width="8" fill="none" />
            @foreach ([[51, 41], [75, 31], [100, 28], [125, 32], [149, 43]] as [$flowerX, $flowerY])
                <g transform="translate({{ $flowerX }} {{ $flowerY }})">
                    <path d="M-6 1Q-18 -9 -17 6Q-10 12 -6 1M7 0Q20 -8 18 7Q11 11 7 0" fill="#77bf8b" />
                    <circle cy="-7" r="6" fill="#fff4e3" /><circle cx="7" cy="-2" r="6" fill="#fff4e3" /><circle cx="4" cy="6" r="6" fill="#fff4e3" /><circle cx="-5" cy="6" r="6" fill="#fff4e3" /><circle cx="-7" cy="-2" r="6" fill="#fff4e3" />
                    <circle r="5" fill="#efbc52" />
                </g>
            @endforeach
        </g>
        <g data-avatar-part="headwear" data-avatar-value="beret" style="display: {{ $look['headwear'] === 'beret' ? 'inline' : 'none' }}">
            <path d="M42 42Q29 14 91 9Q145 -2 159 26Q166 43 143 48L64 53Z" fill="#cc789d" />
            <path d="M47 43Q100 38 149 43L143 56Q95 52 53 59Z" fill="#974467" />
            <path d="M111 12L115 5" stroke="#974467" stroke-width="7" />
            <path d="M51 27Q63 16 92 16" stroke="#f0afca" stroke-width="4" fill="none" />
            <path d="M130 33L133 39L140 40L135 45L136 52L130 48L124 52L125 45L120 40L127 39Z" fill="#f5d188" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="messenger_bag" style="display: {{ $look['accessory'] === 'messenger_bag' ? 'inline' : 'none' }}">
            <path d="M76 132L135 192" stroke="#d1a26c" stroke-width="8" fill="none" />
            <rect x="112" y="178" width="46" height="40" rx="7" fill="#548e85" />
            <path d="M113 180H157V194Q136 205 113 194Z" fill="#8dc0a8" />
            <rect x="132" y="190" width="8" height="14" rx="2" fill="#f2d191" />
            <path d="M119 207H129" stroke="#c8e4ca" stroke-width="3" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="explorer_watch" style="display: {{ $look['accessory'] === 'explorer_watch' ? 'inline' : 'none' }}">
            <path d="M36 199L58 205" stroke="#355b61" stroke-width="12" />
            <rect x="38" y="194" width="18" height="16" rx="5" fill="#edbf65" transform="rotate(15 47 202)" />
            <circle cx="47" cy="202" r="6" fill="#263f54" /><path d="M47 198V202L51 204" stroke="#7ce0cb" stroke-width="2" fill="none" />
        </g>
        <g data-avatar-part="accessory" data-avatar-value="star_purse" style="display: {{ $look['accessory'] === 'star_purse' ? 'inline' : 'none' }}">
            <path d="M77 133Q112 156 142 189" stroke="#e7b859" stroke-width="4" fill="none" />
            <path d="M138 171L146 184L161 188L150 199L151 215L137 208L123 215L124 199L113 188L130 184Z" fill="#dc7eac" />
            <path d="M138 178L144 188L156 190L147 199L148 209L137 204L128 209L129 198L121 190L132 188Z" fill="#f4b7d3" />
            <circle cx="137" cy="192" r="4" fill="#ffdc8c" />
        </g>
    </g>
</svg>
