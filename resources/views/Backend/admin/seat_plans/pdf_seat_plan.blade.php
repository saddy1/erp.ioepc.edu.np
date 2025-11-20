<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Seat Plan - {{ $examDate }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
        }

        .page-wrapper {
            padding: 10px 12px;
        }

        /* ===== MAIN HEADER ===== */
        .main-header {
            text-align: center;
            margin-bottom: 12px;
            padding: 8px;
            border: 2px solid #000;
            background: #f3f3f3;
        }

        .main-header-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 4px;
        }

        .main-header-meta {
            font-size: 11px;
            line-height: 1.3;
        }

        /* ===== ROOM HEADER ===== */
        .room-card {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .room-header {
            border-radius: 6px;
            border: 2px solid #c4c4c4;
            background: #f0f0f0;
            padding: 8px 10px;
            margin-bottom: 8px;
        }

        .room-header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .room-title {
            font-size: 16px;
            font-weight: bold;
            color: #222;
        }

        .room-meta {
            margin-top: 3px;
            font-size: 10px;
            color: #165b25;
        }

        .invigilator-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
            text-align: right;
        }

        .invigilator-badges span {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 7px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid;
            white-space: nowrap;
            margin-left: 3px;
            margin-bottom: 3px;
        }

        .badge-faculty {
            background: #e5f5e8;
            border-color: #8cc79b;
            color: #256331;
        }

        .badge-staff {
            background: #f0e6ff;
            border-color: #c19af9;
            color: #5b2ca0;
        }

        /* ===== OUTER COLUMNS TABLE ===== */
        .columns-table {
            width: 100%;
            border-collapse: collapse;
        }

        .columns-table th {
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            color: #555;
            border-bottom: 2px solid #d0d0d0;
            padding-bottom: 4px;
        }

        .columns-table td {
            vertical-align: top;
            padding-top: 8px;
        }

        .col-spacing {
            padding-right: 10px;
        }

        /* ===== INNER TABLE: ONE ROW (R1) IN ONE COLUMN ===== */
        .bench-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .row-label-cell {
            width: 22px;
            font-size: 10px;
            font-weight: bold;
            color: #777;
            vertical-align: top;
            padding-right: 4px;
            white-space: nowrap;
        }

        .bench-cell {
            vertical-align: top;
        }

        /* ===== BENCH WRAPPER (BOX) ===== */
        .bench-wrapper {
            border: 2px solid #d0d0d0;
            background: #f6f6f6;
            border-radius: 10px;
            padding: 4px 5px;
        }

        .bench-wrapper.same-subject {
            border-color: #f2b600;
            background: #fff7e1;
        }

        .bench-wrapper.empty {
            border-style: dashed;
            background: #fafafa;
        }

        /* ===== INNER BENCH TABLE (1 × 2) – ONLY SYMBOL NUMBERS ===== */
        .bench-inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bench-inner-table td {
            border: 1px solid #dddddd;
            padding: 5px 6px;
        }

        .bench-symbol-left {
            font-family: "Courier New", monospace;
            font-size: 15px;
            font-weight: bold;
            text-align: left;
            width: 50%;
        }

        .bench-symbol-right {
            font-family: "Courier New", monospace;
            font-size: 15px;
            font-weight: bold;
            text-align: right;
            width: 50%;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #bfbfbf;
            padding-top: 6px;
            margin-top: 12px;
        }
          .page-wrapper-room {
        page-break-inside: avoid;
    }

    .page-break-before {
        page-break-before: always;
    }
    </style>
</head>

<body>
    <div class="page-wrapper">

        @foreach ($seatLayout as $roomId => $data)
            @php
                $room = $data['room'];
                $cols = $data['cols'];
                $invigilators = $data['invigilators'] ?? [];
                $maxRows = max(count($cols[1] ?? []), count($cols[2] ?? []), count($cols[3] ?? []));
            @endphp

            {{-- One page per room --}}
            <div class="page-wrapper-room {{ !$loop->first ? 'page-break-before' : '' }}">
                <!-- MAIN HEADER -->
                <div class="main-header">
                    <div class="main-header-title">Examination Seat Plan</div>
                    <div class="main-header-meta">
                        <strong>{{ $exam->exam_title }}</strong> |
                        Semester: {{ $exam->semester }} |
                        Batch: {{ ucfirst($exam->batch) }} |
                        Date: <strong>{{ $examDate }}</strong>
                        @if ($exam->start_time && $exam->end_time)
                            | Time:
                            {{ \Carbon\Carbon::parse($exam->start_time)->format('h:i A') }}
                            -
                            {{ \Carbon\Carbon::parse($exam->end_time)->format('h:i A') }}
                        @endif
                    </div>
                </div>

                <div class="room-card">
                    <!-- ROOM HEADER -->
                    <div class="room-header">
                        <table class="room-header-top">
                            <tr>
                                <td>
                                    <div class="room-title">Room {{ $room->room_no }}</div>
                                    <div class="room-meta">
                                        Benches: <strong>{{ $room->computed_total_benches }}</strong>
                                        • Seats: <strong>{{ $room->computed_total_seats }}</strong>
                                    </div>
                                </td>
                                {{-- invigilators section if you want to show it --}}
                            </tr>
                        </table>
                    </div>

                    <!-- COLUMNS TABLE -->
                    <table class="columns-table">
                        <thead>
                            <tr>
                                <th class="col-spacing">Column 1</th>
                                <th class="col-spacing">Column 2</th>
                                <th>Column 3</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($rowIdx = 1; $rowIdx <= $maxRows; $rowIdx++)
                                <tr>
                                    @for ($c = 1; $c <= 3; $c++)
                                        @php
                                            $rows = $cols[$c] ?? [];
                                            $bench = $rows[$rowIdx] ?? null;

                                            $left = $bench['left'] ?? null;
                                            $right = $bench['right'] ?? null;

                                            $sameSubject = $left && $right && $left['subject_code'] === $right['subject_code'];
                                        @endphp

                                        <td class="{{ $c < 3 ? 'col-spacing' : '' }}">
                                            <table class="bench-row-table">
                                                <tr>
                                                    <td class="row-label-cell">R{{ $rowIdx }}</td>
                                                    <td class="bench-cell">
                                                        @if ($bench)
                                                            <div class="bench-wrapper {{ $sameSubject ? 'same-subject' : '' }}">
                                                                <table class="bench-inner-table">
                                                                    <tr>
                                                                        <td class="bench-symbol-left">
                                                                            @if ($left && !empty($left['symbol_no']))
                                                                                {{ $left['symbol_no'] }}
                                                                            @else
                                                                                &mdash;
                                                                            @endif
                                                                        </td>
                                                                        <td class="bench-symbol-right">
                                                                            @if ($right && !empty($right['symbol_no']))
                                                                                {{ $right['symbol_no'] }}
                                                                            @else
                                                                                &mdash;
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="footer">
            Generated on {{ date('d/m/Y h:i A') }} |
            {{ $exam->exam_title }} – {{ $examDate }}
        </div>
    </div>
</body>


</html>
