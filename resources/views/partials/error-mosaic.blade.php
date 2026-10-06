{{--
    Renders $code (e.g. "404") as a static mosaic built from real site
    photos, dot-matrix style -- inspired by Dribbble's tile-based 404 page,
    but static (no shuffle animation) and using this site's own photos.
--}}
@php
    $digitBitmaps = [
        '0' => ['01110', '10001', '10011', '10101', '11001', '10001', '01110'],
        '1' => ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
        '2' => ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
        '3' => ['11111', '00010', '00100', '00010', '00001', '10001', '01110'],
        '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
        '5' => ['11111', '10000', '11110', '00001', '00001', '10001', '01110'],
        '6' => ['00110', '01000', '10000', '11110', '10001', '10001', '01110'],
        '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
        '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
        '9' => ['01110', '10001', '10001', '01111', '00001', '00010', '01100'],
    ];

    $digits = str_split((string) $code);

    $totalOnCells = 0;
    foreach ($digits as $d) {
        foreach ($digitBitmaps[$d] ?? [] as $row) {
            $totalOnCells += substr_count($row, '1');
        }
    }

    $mosaicPhotos = \App\Models\Photo::where('status', 'ready')
        ->whereNotNull('thumbnail_path')
        ->inRandomOrder()
        ->limit(max($totalOnCells, 1))
        ->get();

    $photoCount = $mosaicPhotos->count();
    $photoIndex = 0;
@endphp
<div class="error-mosaic">
    @foreach ($digits as $d)
        <div class="error-mosaic-digit">
            @foreach ($digitBitmaps[$d] ?? [] as $row)
                @foreach (str_split($row) as $cell)
                    @if ($cell === '1')
                        @php
                            $photo = $photoCount ? $mosaicPhotos[$photoIndex % $photoCount] : null;
                            $photoIndex++;
                        @endphp
                        <div class="error-mosaic-cell error-mosaic-cell-on">
                            @if ($photo)
                                <img src="{{ route('photos.thumbnail', $photo) }}" alt="">
                            @endif
                        </div>
                    @else
                        <div class="error-mosaic-cell"></div>
                    @endif
                @endforeach
            @endforeach
        </div>
    @endforeach
</div>
