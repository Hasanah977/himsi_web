@props(['kind' => 'card'])
<svg {{ $attributes->merge(['class' => 'member-art']) }} viewBox="0 0 240 200" fill="none" aria-hidden="true" focusable="false">
    <ellipse cx="123" cy="178" rx="83" ry="10" fill="#25365c" opacity=".09"/>
    @switch($kind)
    @case('community')
        <circle cx="124" cy="97" r="83" fill="white" opacity=".08"/>
        <path d="M20 170v-37c0-21 19-35 40-35s40 14 40 35v37" fill="#f4b95c"/>
        <circle cx="60" cy="75" r="24" fill="#f5c3a7"/><path d="M36 76V65c0-31 47-34 48 0-12 4-21-2-27-10-1 11-8 18-21 21" fill="#27365c"/>
        <path d="M93 174V99c0-20 15-32 32-32s32 12 32 32v75" fill="#8cb8df"/>
        <circle cx="125" cy="47" r="23" fill="#f6c5a8"/><path d="M102 49c-8-34 44-41 47-10-13 7-28 5-35-4l-12 14" fill="#27365c"/>
        <path d="M158 173v-47c0-19 14-32 32-32s32 13 32 32v47" fill="#e49291"/>
        <circle cx="190" cy="72" r="23" fill="#f5c3a7"/><path d="M168 73c-9-35 42-43 46-10-16 5-28 0-33-7l-13 17" fill="#27365c"/>
        <rect x="74" y="133" width="81" height="44" rx="7" fill="#e8effa"/><path d="m74 133 5 44h71l5-44" fill="#d8e3f5"/><circle cx="115" cy="154" r="5" fill="white"/>
        <rect x="17" y="174" width="210" height="6" rx="3" fill="#d5e4ef"/>
        <path d="m24 37 21-8 5 19-20 7Z" fill="#ffdf80"/><path d="m195 25 6-11 7 11-7 11Z" fill="#ffdf80"/><circle cx="218" cy="97" r="4" fill="#ffdf80"/>
        @break
    @case('wallet')
        <circle cx="138" cy="65" r="29" fill="#f3c35c"/><circle cx="138" cy="65" r="20" stroke="#fff1ba" stroke-width="4"/><path d="M139 52v26m7-21c-13-9-22 8-7 8 15 0 6 17-7 8" stroke="#c38a2c" stroke-width="3" stroke-linecap="round"/>
        <rect x="43" y="79" width="149" height="87" rx="19" fill="#537fb2"/><path d="M44 97c0-18 12-26 28-26h91v25" fill="#94b9e3"/><rect x="43" y="86" width="149" height="80" rx="16" fill="#779ecd"/>
        <rect x="149" y="110" width="53" height="35" rx="10" fill="#b4a7db"/><circle cx="164" cy="127" r="5" fill="#f8e5aa"/>
        <ellipse cx="59" cy="166" rx="27" ry="8" fill="#d3a242"/><path d="M32 149v15c0 11 54 11 54 0v-15" fill="#edbf62"/><ellipse cx="59" cy="149" rx="27" ry="8" fill="#ffdf8c"/><path d="M33 155c13 8 39 8 52 0" stroke="#d7a344" stroke-width="3"/>
        @break
    @case('aspiration')
        <path d="m40 114 73-20 80 26-74 23Z" fill="#dfe5f4"/><path d="m40 114 79 29v35l-79-27Z" fill="#b3bedc"/><path d="m119 143 74-23v33l-74 25Z" fill="#889bc2"/>
        <path d="m68 67 140-35-48 77-28-28-38 16 7-27Z" fill="#a8c4e3"/><path d="m68 67 140-35-76 49Z" fill="#e7f1fb"/><path d="m132 81 28 28 48-77Z" fill="#c8dcef"/><path d="m101 70 107-38-76 49-15 18" stroke="#799ac4" stroke-width="3" stroke-linejoin="round"/>
        <path d="m37 66 13-6m-1 28 11-2" stroke="#eac261" stroke-width="5" stroke-linecap="round"/>
        @break
    @case('calendar')
        <g transform="rotate(-9 100 100)"><rect x="31" y="42" width="133" height="120" rx="15" fill="#b4c9e6"/><rect x="31" y="42" width="133" height="28" rx="12" fill="#e99191"/><path d="M56 34v23m80-23v23" stroke="#677b9f" stroke-width="9" stroke-linecap="round"/><path d="M53 91h14m17 0h14m17 0h14m-76 23h14m17 0h14m17 0h14m-76 23h14m17 0h14" stroke="white" stroke-width="9" stroke-linecap="round"/></g>
        <g transform="rotate(10 159 127)"><rect x="111" y="84" width="89" height="98" rx="9" fill="#edf1f9"/><path d="M127 105h55m-55 17h55m-55 17h39m-39 17h47" stroke="#a5b6d4" stroke-width="5" stroke-linecap="round"/></g>
        <path d="m204 49 3 9 9 3-9 3-3 9-3-9-9-3 9-3Z" fill="#eac261"/>
        @break
    @case('ballot')
        <path d="m43 104 79-23 76 27-78 26Z" fill="#c6d6ea"/><path d="m43 104 77 30v45l-77-28Z" fill="#8ba4c5"/><path d="m120 134 78-26v45l-78 26Z" fill="#6b85ad"/><path d="m96 105 50 3" stroke="#334c76" stroke-width="7" stroke-linecap="round"/>
        <g transform="rotate(12 121 68)"><rect x="87" y="22" width="73" height="83" rx="6" fill="#f6f8fd"/><path d="M103 39h41" stroke="#e69b9d" stroke-width="7" stroke-linecap="round"/><rect x="103" y="55" width="27" height="27" rx="5" stroke="#9bafce" stroke-width="3"/><path d="m109 68 6 6 13-16" stroke="#3f9c89" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></g>
        <circle cx="192" cy="58" r="17" fill="#fff0b9"/><path d="m185 58 5 5 9-11" stroke="#b28c32" stroke-width="3" stroke-linecap="round"/>
        @break
    @case('trophy')
        <path d="M78 57H48c-3 42 15 57 44 57m70-57h30c3 42-15 57-44 57" stroke="#efb329" stroke-width="11" stroke-linecap="round"/>
        <path d="M75 45h90v27c0 38-17 57-45 57S75 110 75 72Z" fill="#ffc83d"/><path d="M120 45h45v27c0 38-17 57-45 57Z" fill="#f4ad20"/><path d="M90 56v20c0 13 3 24 10 32" stroke="#ffe891" stroke-width="7" stroke-linecap="round"/>
        <path d="M113 128h14v25h21v12H92v-12h21Z" fill="#efb329"/><rect x="85" y="163" width="70" height="17" rx="3" fill="#73515b"/><rect x="98" y="167" width="44" height="9" rx="2" fill="#f8c95b"/>
        <path d="m120 10 4 10 11 1-8 7 2 11-9-6-10 6 3-11-8-7 11-1Z" fill="#ffd55b"/><path d="m37 111 3 7 8 1-6 5 1 8-6-4-7 4 2-8-6-5 8-1Z" fill="#ffd55b"/><path d="m192 114 3 7 8 1-6 5 1 8-6-4-7 4 2-8-6-5 8-1Z" fill="#ffd55b"/>
        <path d="m44 33 8 8m135-8-7 8m-132 105 7-8m128 8-7-8" stroke="#5ba99c" stroke-width="5" stroke-linecap="round"/><path d="m69 17-3 10m100-10 4 10m-145 57 10-4m174 0-10-4" stroke="#9a7ac1" stroke-width="5"/>
        @break
    @default
        <g transform="rotate(9 120 110)"><rect x="39" y="63" width="160" height="103" rx="16" fill="#aec9dd"/><rect x="49" y="75" width="140" height="80" rx="10" fill="#d9e8f0"/><rect x="62" y="87" width="43" height="54" rx="7" fill="#f6f8fc"/><circle cx="83" cy="103" r="10" fill="#e6b2a0"/><path d="M68 132c0-21 30-21 30 0" fill="#8eabd3"/><path d="M119 100h52m-52 17h42m-42 17h29" stroke="#8babc7" stroke-width="6" stroke-linecap="round"/></g>
        <path d="m108 27 12 24 14-24h-11l-5 10-5-10Z" fill="#dc8f83"/><circle cx="121" cy="58" r="17" fill="#f4c65b"/><path d="m121 46 3 7 8 1-6 5 1 8-6-4-7 4 2-8-6-5 8-1Z" fill="#ffe9a4"/>
    @endswitch
</svg>
