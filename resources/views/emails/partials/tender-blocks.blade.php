{{-- Alinea's en opsommingen uit TenderText::blocks, in de maat van de plek waar ze staan. --}}
@foreach($blocks as $block)
    @if($block['type'] === 'ul')
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 {{ $gap }};">
            @foreach($block['lines'] as $line)
                <tr>
                    <td style="width:18px;padding:3px 0;font-size:{{ $size }};line-height:1.6;color:{{ $color }};vertical-align:top;">•</td>
                    <td style="padding:3px 0;font-size:{{ $size }};line-height:1.6;color:{{ $ink }};vertical-align:top;">{{ $line }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="font-size:{{ $size }};line-height:1.65;color:{{ $ink }};margin:0 0 {{ $gap }};">{!! implode('<br>', array_map('e', $block['lines'])) !!}</p>
    @endif
@endforeach
