<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Venue Summary - {{ $semester->name ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px; text-align: center; }
        .header { background: #6f42c1; color: white; font-weight: bold; }
        .time { background: #f8f9fa; font-weight: bold; position: sticky; left: 0; z-index: 10; }
        .venue-header { background: #6f42c1; color: white; writing-mode: vertical-rl; text-orientation: mixed; }
        .booked { background: #e7d4ff; font-weight: bold; color: #6f42c1; }
        .free { background: #f8f9fa; color: #28a745; }
        .logo { text-align: center; margin: 20px 0; }
        .title { text-align: center; font-size: 16pt; margin: 10px 0; color: #6f42c1; }
    </style>
</head>
<body>
    <div class="logo">
        <img src="{{ public_path('images/logo.png') }}" width="80" alt="Logo">
    </div>
    <div class="title">
        VENUE USAGE SUMMARY<br>
        <small>{{ $semester->name ?? 'N/A' }} ({{ $academicYear ?? 'N/A' }})</small>
    </div>

    <table>
        <thead>
            <tr class="header">
                <th rowspan="2" class="time">Time</th>
                @foreach($days as $day)
                    <th colspan="{{ count($venues) }}">{{ $day }}</th>
                @endforeach
            </tr>
            <tr class="header">
                @foreach($days as $day)
                    @foreach($venues as $venue)
                        <th class="venue-header">
                            {{ $venue->name }}<br>
                            <small>{{ $venue->longform }} ({{ $venue->capacity }})</small>
                        </th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($hours as $hour)
                <tr>
                    <td class="time">{{ substr($hour, 0, 5) }}</td>
                    @foreach($days as $day)
                        @foreach($venues as $venue)
                            @php $cell = $grid[$day][$hour][$venue->id] ?? null; @endphp
                            @if($cell && $cell['isFirst'])
                                <td class="booked" rowspan="{{ $cell['rowspan'] }}">
                                    {{ $cell['content'] }}<br>
                                    <small><strong>BOOKED</strong></small>
                                </td>
                            @elseif(!$cell || !$cell['isFirst'])
                                @continue
                            @else
                                <td class="free">FREE</td>
                            @endif
                        @endforeach
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 8pt; color: #666;">
        Generated on {{ now()->format('d M Y, h:i A') }} | TechNest © {{ date('Y') }}
    </div>
</body>
</html>