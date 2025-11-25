<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Seat Plan - {{ $examDate }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #111;
        }

        .page-wrapper {
            padding: 5px 8px;
        }

        /* ===== MAIN HEADER ===== */
        .main-header {
            text-align: center;
            margin-bottom: 6px;
            padding: 5px;
            border: 1.5px solid #000;
            background: #f3f3f3;
        }

        .main-header-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 2px;
        }

        .main-header-meta {
            font-size: 8px;
            line-height: 1.2;
        }

        /* ===== ROOM HEADER ===== */
        .room-card {
            margin-bottom: 10px;
        }

        .room-title {
            font-size: 20px;
            font-weight: bold;
            color: #222;
            margin-bottom: 4px;
        }

        /* ===== TWO COLUMN LAYOUT TABLE ===== */
        .two-column-table {
            width: 100%;
            border-collapse: collapse;
            page-break-after: always;
            margin-bottom: 15px;
        }

        .two-column-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }

        .two-column-table td:first-child {
            border-right: 2px dashed #888;
            padding-right: 8px;
        }

        .two-column-table td:last-child {
            padding-left: 8px;
        }

        /* ===== OUTER COLUMNS TABLE ===== */
        .columns-table {
            width: 100%;
            border-collapse: collapse;
        }

        .columns-table th {
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #555;
            border-bottom: 1.5px solid #d0d0d0;
            padding-bottom: 3px;
            padding-right: 4px;
        }

        .columns-table td {
            vertical-align: top;
            padding-top: 4px;
            padding-right: 4px;
        }

        /* ===== INNER TABLE: ONE ROW (R1) IN ONE COLUMN ===== */
        .bench-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .row-label-cell {
            width: 16px;
            font-size: 8px;
            font-weight: bold;
            color: #777;
            vertical-align: top;
            padding-right: 3px;
            white-space: nowrap;
        }

        .bench-cell {
            vertical-align: top;
        }

        /* ===== BENCH WRAPPER (BOX) ===== */
        .bench-wrapper {
            border: 1.5px solid #d0d0d0;
            background: #f6f6f6;
            border-radius: 6px;
            padding: 2px 3px;
            margin-bottom: 2px;
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
            padding: 3px 4px;
        }

        .bench-symbol-left {
            font-family: "Courier New", monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            width: 50%;
        }

        .bench-symbol-right {
            font-family: "Courier New", monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: right;
            width: 50%;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #bfbfbf;
            padding-top: 4px;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">

        @php
        $chunks = array_chunk($seatLayout, 2, true);
        @endphp

        @foreach ($chunks as $chunkIndex => $chunk)
            <table class="two-column-table">
                <tr>
                    @foreach ($chunk as $roomId => $data)
                        @php
                            $room = $data['room'];
                            $cols = $data['cols'];
                            $invigilators = $data['invigilators'] ?? [];
                            $maxRows = max(count($cols[1] ?? []), count($cols[2] ?? []), count($cols[3] ?? []));
                        @endphp

                        <td>
                            <!-- ROOM TITLE -->
                            <div class="room-title">Room {{ $room->room_no }}</div>

                            <!-- EXAM HEADER -->
                            <div class="main-header">
                                <div class="main-header-title">Examination Seat Plan</div>
                                <div class="main-header-meta">
                                    <strong>{{ $exam->exam_title }}</strong> |
                                    Sem: {{ $exam->semester }} |
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

                            <!-- SEATING LAYOUT -->
                            <div class="room-card">
                                <table class="columns-table">
                                    <thead>
                                        <tr>
                                            <th>Column 1</th>
                                            <th>Column 2</th>
                                            <th>Column 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @for ($rowIdx = 1; $rowIdx <= $maxRows; $rowIdx++)
                                            <tr>
                                                @for ($c = 1; $c <= 3; $c++)
                                                    @php
                                                        $rows  = $cols[$c] ?? [];
                                                        $bench = $rows[$rowIdx] ?? null;

                                                        $left  = $bench['left'] ?? null;
                                                        $right = $bench['right'] ?? null;
                                                        $sameSubject = $left && $right && $left['subject_code'] === $right['subject_code'];

                                                        if ($c === 3 && $bench) {
                                                            $hasLeft  = !empty($left);
                                                            $hasRight = !empty($right);
                                                            if ($hasLeft && !$hasRight) {
                                                                $right = $left;
                                                                $left = null;
                                                            }
                                                        }
                                                    @endphp

                                                    <td>
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
                                                                                            —
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="bench-symbol-right">
                                                                                        @if ($right && !empty($right['symbol_no']))
                                                                                            {{ $right['symbol_no'] }}
                                                                                        @else
                                                                                            —
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
                        </td>
                    @endforeach

                    {{-- If only one room in chunk, add empty cell --}}
                    @if (count($chunk) == 1)
                        <td></td>
                    @endif
                </tr>
            </table>
        @endforeach

        <div class="footer">
            Generated on {{ date('d/m/Y h:i A') }} |
            {{ $exam->exam_title }} – {{ $examDate }}
        </div>
    </div>
</body>

</html>