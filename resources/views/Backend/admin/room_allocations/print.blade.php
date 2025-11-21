<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php
        try {
            $formattedExamDate = \Carbon\Carbon::createFromFormat('d/m/Y', $examDate)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                $formattedExamDate = \Carbon\Carbon::parse($examDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $formattedExamDate = $examDate;
            }
        }
    @endphp
    <title>Room Allocation - {{ $exam->exam_title }} ({{ $formattedExamDate }})</title>
    <style>
        /* Page setup: A4 Landscape */
        @page {
            size: A4 landscape;
            margin: 6mm 6mm 8mm 6mm; /* top right bottom left */
        }

        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            box-sizing: border-box;
            font-weight: 600;
            line-height: 1.2;
        }

        body {
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        .wrapper {
            padding: 4mm 4mm 3mm 4mm; /* inner padding inside page */
            transform-origin: top left;
            transform: scale(0.95); /* slight scale to help fit one A4 page */
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .header-left h1 {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            margin: 0 0 2px 0;
            letter-spacing: -0.3px;
        }

        .header-meta {
            font-size: 10px;
            color: #000;
        }

        .header-meta div {
            margin-bottom: 1px;
        }

        .header-meta span {
            font-weight: 800;
            color: #000;
        }

        .header-right {
            display: flex;
            gap: 15px;
        }

        .header-right-box {
            text-align: right;
            padding: 6px 8px;
            background: transparent;
            border-radius: 4px;
            border: 1px solid #000;
        }

        .header-right-box .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000;
            margin-bottom: 2px;
        }

        .header-right-box .value {
            font-size: 13px;
            font-weight: 700;
            color: #000;
        }

        /* Table Styles */
        table {
            border-collapse: collapse;   /* simpler for PDF */
            width: 100%;
            table-layout: fixed;        /* force columns to fit page width */
        }

        th, td {
            padding: 4px 4px;
            vertical-align: middle;
            border: 1px solid #000;
            word-wrap: break-word;      /* allow wrapping in cells */
        }

        thead th {
            background: transparent;
            color: #000;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #000;
        }

        thead th:first-child {
            width: 90px;                /* narrower room col */
        }

        thead th:last-child {
            width: 95px;                /* room total col */
        }

        thead th .subject-code {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 1px;
        }

        thead th .subject-name {
            font-size: 8px;
            font-weight: 400;
            margin-bottom: 1px;
        }

        thead th .faculty-code {
            font-size: 9px;
            padding: 1px 3px;
            border-radius: 2px;
            display: inline-block;
            margin-top: 1px;
            background: transparent;
            color: #000;
        }

        thead th .total-badge {
            font-size: 9px;
            margin-top: 1px;
            padding: 2px 3px;
            border-radius: 8px;
            display: inline-block;
            background: transparent;
            color: #000;
        }

        tbody td {
            font-size: 10px;
        }

        tbody td:first-child {
            font-weight: 500;
        }

        .room-info .room-no {
            font-size: 11px;
            font-weight: 700;
            color: #000;
            margin-bottom: 2px;
            text-align: center;
        }

        .room-info .room-details {
            font-size: 8px;
            color: #000;
            line-height: 1.3;
        }

        .text-center {
            text-align: center;
        }

        /* Pills/Badges */
        .pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 10px;
            border: 1px solid #000;
            background: transparent;
            color: #000;
        }

        /* Subject total row */
        .subject-total-row {
            font-weight: 600;
        }

        .subject-total-row td {
            padding: 6px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            border-radius: 8px;
            border: 1px dashed #000;
            font-size: 12px;
            color: #000;
        }

        /* Footer */
        .footer {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: #000;
        }

        .legend {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 2px;
            background: #000;
        }

        /* Signature block */
        .signature-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            padding: 10px 8px;
            vertical-align: top;
            border: 1px solid #0a0000;
        }
        .header-table td {
            width: 50%;
            padding: 10px 8px;
            vertical-align: top;
            border: #fff;
        }

        .signature-label {
            font-weight: 700;
            font-size: 10px;
            color: #000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 16px;
            padding-top: 4px;
            color: #000;
            font-size: 9px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <h1>📋 Room Allocation Report</h1>
            <div class="header-meta">
                <div style="font-size: larger">Exam:{{ $exam->exam_title }} Batch: {{ $batch == 1 ? 'New Batch' : 'Old Batch' }}</div>
                
            </div>
        </div>
   
    </div>
    <table class="header-table" >
        <tr>
            <td>
                <div class="signature-label">Exam Date</div>
                <div style="font-size: large; text-decoration: underline;">{{ $formattedExamDate }}</div>
            </td>
            <td style="text-align: right">
                <div class="signature-label">Total Students</div>
                <div style="font-size: large">{{ $totalStudents }}</div>
            </td>
        </tr>
    </table>

    @if($rooms->isEmpty() || empty($papers))
        <div class="empty-state">
            No room allocations found for this exam and date.
        </div>
    @else
        @php
            $usedRooms = $rooms->filter(function ($room) use ($totalsByRoom) {
                return ($totalsByRoom[$room->id] ?? 0) > 0;
            });
            
            $usedRoomsCount = $usedRooms->count();
            $usedRoomsCount = $usedRoomsCount > 0 ? $usedRoomsCount : $rooms->count();
            
            // Calculate total capacity of all used rooms
            $totalRoomCapacity = $usedRooms->sum('computed_total_seats');
            // If no rooms are used, fall back to all rooms' capacity
            if ($totalRoomCapacity == 0) {
                $totalRoomCapacity = $rooms->sum('computed_total_seats');
            }
        @endphp
        <table>
            <thead>
            <tr>
                <th style="text-align:center; font-size: 15px;">Room</th>
                @foreach($papers as $paperKey => $paper)
                    @php
                        $fac = $faculties[$paper['faculty_id']] ?? null;
                    @endphp
                    <th>
                        <div class="subject-code">{{ $paper['subject_code'] }}</div>
                        <div class="subject-name">{{ $paper['subject_name'] }}</div>
                        <div class="faculty-code" style="font-size: larger">{{ $fac?->code ?? 'N/A' }}</div>
                        <div class="total-badge" style="font-size: larger">= {{ $paper['total_students'] }}</div>
                    </th>
                @endforeach
                <th>Stu / Room</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rooms as $room)
                @php
                    $roomTotal = $totalsByRoom[$room->id] ?? 0;
                    $roomCap   = $room->computed_total_seats;
                    $overCap   = $roomTotal > $roomCap;
                    $utilization = $roomCap > 0 ? ($roomTotal / $roomCap) * 100 : 0;
                @endphp
                <tr>
                    <td>
                        <div class="room-info">
                            <div class="room-no">{{ $room->room_no }}</div>
                            {{-- <div class="room-details">
                                Capacity: {{ $roomCap }} seats<br>
                                Benches: {{ $room->computed_total_benches }}
                            </div> --}}
                        </div>
                    </td>
                    @foreach($papers as $paperKey => $paper)
                        @php
                            $val = $allocByRoom[$room->id][$paperKey] ?? 0;
                        @endphp
                        <td class="text-center">
                            @if($val > 0)
                                <span style="font-size: 13px">{{ $val }}</span>
                            @else
                                <span>—</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-center">
                        <span class="pill {{ $overCap ? 'pill-danger' : ($utilization > 80 ? 'pill-warning' : 'pill-success') }}">
                            {{ $roomTotal }} / {{ $roomCap }}
                        </span>
                    </td>
                </tr>
            @endforeach

            {{-- Subject Totals Row --}}
            <tr class="subject-total-row">
                <td><span style="font-size: 13px; text-align: center;">Subject Total</span></td>
                @foreach($papers as $paperKey => $paper)
                    @php
                        $paperTotal = $totalsByPaper[$paperKey] ?? 0;
                        $paperMax   = $paper['total_students'];
                        $overAlloc  = $paperTotal > $paperMax;
                        $underAlloc = $paperTotal < $paperMax;
                    @endphp
                    <td class="text-center">
                        <span class="pill {{ $overAlloc ? 'pill-danger' : ($underAlloc ? 'pill-warning' : 'pill-success') }}">
                            {{ $paperTotal }} / {{ $paperMax }}
                        </span>
                    </td>
                @endforeach
                <td class="text-center">
                    <span class="pill">{{ $totalStudents }} / {{ $totalStudents }}</span>
                </td>
            </tr>
            </tbody>
        </table>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-label">Prepared By</div>
                <div class="signature-line">Signature & Date</div>
            </td>
            <td>
                <div class="signature-label">Approved By</div>
                <div class="signature-line">Signature & Date</div>
            </td>
        </tr>
    </table>
    <div class="footer">
        <div style="text-align: center; font-size: large; font-style: italic; margin-top: 10px;">Generated by ERC Exam System</div>
        <div>Generated: <span>{{ now()->format('d M Y, H:i') }}</span></div>
    </div>
</div>
</body>
</html>
