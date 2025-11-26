<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invigilator Map</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .subtitle {
            text-align: center;
            font-size: 11px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            border: 1px solid #444;
            padding: 4px 6px;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .small {
            font-size: 10px;
        }
        .center {
            text-align: center;
        }
        .arrow-cell {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }
        .section-header {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 4px;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="title">
        Invigilator Allocation Sheet
    </div>
    <div class="subtitle">
        Exam: {{ $exam->exam_title ?? 'N/A' }} &nbsp; | &nbsp;
        Date: {{ $examDate }}
    </div>

    {{-- SECTION 1: Room-wise invigilators --}}
    <div class="section-header">1. Room-wise Invigilators</div>
    <table>
        <thead>
        <tr>
            <th style="width: 70px;">Room</th>
            <th>Invigilators</th>
        </tr>
        </thead>
        <tbody>
        @foreach($seatLayout as $roomId => $layout)

    @php
        $room = $layout['room'];
        $invs = collect($layout['invigilators'] ?? []);
    @endphp

    <tr>
        <td class="center">{{ $room->room_no }}</td>
        <td>
            @if ($invs->isEmpty())
                <span class="small" style="color:#777;">(No invigilator assigned)</span>
            @else
                @foreach($invs as $idx => $inv)
                    {{ $idx + 1 }}.
                    {{ $inv->full_name }}
                    ({{ ucfirst($inv->employee_type) }})
                    @if(!$loop->last)<br>@endif
                @endforeach
            @endif
        </td>
    </tr>

@endforeach

        </tbody>
    </table>

  

</body>
</html>
