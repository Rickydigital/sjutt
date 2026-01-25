<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Examination Timetable PDF</title>
  <style>
    body { margin:0; padding-top: 95px; font-family: Arial, sans-serif; }

    .print-header{
      position: fixed; top:0; left:0; right:0;
      height: 86px; background:#fff;
      text-align:center; padding:6px 0;
      border-bottom: 2px solid #4B2E83;
      z-index: 10000; line-height:1.2;
    }
    .print-header .main-title{ color:#4B2E83; font-weight:bold; font-size:12.5pt; margin:0; }
    .print-header .subtitle{ font-size:9pt; color:#333; margin:2px 0; }
    .print-header .draft{ font-weight:bold; color:#4B2E83; font-size:8.5pt; }
    .print-header .logo{ height: 28px; margin-top: 3px; }

    .page-break { page-break-before: always; }

    table { width:100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
    thead { display: table-header-group !important; }
    tr { page-break-inside: avoid !important; }

    th, td {
      border: 1.4px solid #4B2E83;
      padding: 2px 2px;
      text-align: center;
      vertical-align: middle;
      font-size: 7pt;
    }

    .program-row th{
      background:#4B2E83 !important;
      color:#fff !important;
      font-size: 10.5pt !important;
      font-weight:bold;
      padding:6px 4px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .day-header th{
      background:#4B2E83 !important;
      color:#fff !important;
      font-weight:bold;
      font-size:7.5pt;
      padding:3px 2px;
    }

    .time-cell{
      background:#f5f5f5;
      font-weight:bold;
      width: 130px;
      font-size: 7pt;
    }

    .class-cell{
      background:#5b3aa6;
      color:#fff;
      font-weight:bold;
      width: 140px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .session{
      background:#fff;
      border: 0.7px solid #fff;
      border-radius: 2px;
      padding: 2px 3px;
      margin: 1px 0;
      font-size: 6.5pt;
      line-height: 1.15;
    }
    .session strong{ font-size: 6.8pt; display:block; }

    @page { margin: 1.1cm 0.7cm 1.1cm 0.7cm; size: A4 portrait; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  </style>
</head>

<body>
  <div class="print-header">
    <div class="main-title">ST JOHN'S UNIVERSITY OF TANZANIA</div>
    <div class="subtitle">
      Examination Timetable • {{ $setup->academic_year }} • {{ $setup->semester?->name ?? 'Semester' }}
    </div>
    <div class="draft">{{ $draft }}</div>
    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
  </div>

  @php
    // ✅ Put Week 1 & Week 2 on same page => group chunks by 2
    $chunkPairs = array_chunk($dateChunks, 2);
    $firstPage = true;

    // helper: build comma venue list, excluding allocated_capacity = 0
    $venueNames = function($tt) {
      $names = [];
      foreach ($tt->venues as $v) {
        $cap = (int)($v->pivot->allocated_capacity ?? 0);
        if ($cap <= 0) continue; // ✅ hide 0 capacity venues in PDF
        $names[] = $v->name;
      }
      $names = array_values(array_unique($names));
      return implode(', ', $names);
    };
  @endphp

  @foreach($programs as $program)
    @php
      $programClasses = $classesByProgram[$program->id] ?? collect();
    @endphp

    @foreach($chunkPairs as $pairIndex => $pair)
      @if(!$firstPage)
        <div class="page-break"></div>
      @endif
      @php $firstPage = false; @endphp

      {{-- ✅ Each page contains 1 or 2 weeks (tables) --}}
      @foreach($pair as $pairTableIndex => $chunkDays)
        @php
          // week number based on real chunk index
          $weekNumber = ($pairIndex * 2) + $pairTableIndex + 1;
        @endphp

        <table>
          <thead>
            <tr class="program-row">
              <th colspan="{{ 2 + count($chunkDays) }}">
                {{ $program->short_name }} - {{ $program->name }} (Week {{ $weekNumber }})
              </th>
            </tr>

            <tr class="day-header">
              <th class="time-cell">TIME</th>
              <th>CLASS</th>
              @foreach($chunkDays as $d)
                <th>
                  {{ \Carbon\Carbon::parse($d)->format('d-m') }}
                  ({{ strtoupper(\Carbon\Carbon::parse($d)->format('D')) }})
                </th>
              @endforeach
            </tr>
          </thead>

          <tbody>
          @foreach($timeSlots as $slot)
            @php
              $slotStart = $slot['start_time'];
              $slotEnd   = $slot['end_time'];
              $slotLabel = ($slot['name'] ?? 'Session') . " ({$slotStart}-{$slotEnd})";
            @endphp

            @if($programClasses->isEmpty())
              <tr>
                <td class="time-cell">{{ $slotLabel }}</td>
                <td colspan="{{ 1 + count($chunkDays) }}">No classes found for this program.</td>
              </tr>
            @else
              @foreach($programClasses as $i => $class)
                <tr>
                  @if($i === 0)
                    <td class="time-cell" rowspan="{{ $programClasses->count() }}">
                      {{ $slotLabel }}
                    </td>
                  @endif

                  <td class="class-cell">{{ $class->name }}</td>

                  @foreach($chunkDays as $d)
                    @php
                      $dateKey = \Carbon\Carbon::parse($d)->format('Y-m-d');
                      $items = $grid[$program->id][$class->id][$dateKey][$slotStart] ?? [];
                    @endphp

                    <td>
                      @if(!empty($items))
                        @foreach($items as $tt)
                          @php $vList = $venueNames($tt); @endphp
                          <div class="session">
                            <strong>{{ $tt->course_code }}</strong>
                            {{-- ✅ venues only, comma separated, no capacity --}}
                            <div>{{ $vList ?: '' }}</div>
                          </div>
                        @endforeach
                      @else
                        &nbsp;
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            @endif
          @endforeach
          </tbody>
        </table>
      @endforeach

    @endforeach
  @endforeach

</body>
</html>