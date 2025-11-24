<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Seat Plan</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            margin: 20px;
            line-height: 1.4;
            color: #111;
        }

        h1, h2, h3 {
            font-weight: 700;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1.5px solid #000;
            padding: 8px 10px;
            font-size: 13px;
        }

        th {
            text-align: center;
            background-color: #f3f3f3;
            font-weight: 700;
            font-size: 14px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: 700; }
        .mb-2 { margin-bottom: 12px; }
        .mb-3 { margin-bottom: 18px; }
        .mb-4 { margin-bottom: 24px; }

        /* ===== HEADER ===== */
        .header-container {
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #000;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 6px;
        }

        .header-title-main {
            text-align: center;
            flex: 1;
        }

        .header-college {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .header-subtitle {
            font-size: 14px;
            font-weight: 600;
        }

        .header-exam-title {
            font-size: 16px;
            font-weight: 700;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .header-meta-line {
            font-size: 13px;
            margin-top: 4px;
        }

        .header-time {
            font-size: 14px;
            font-weight: 700;
            text-align: right;
            min-width: 140px;
        }

       /* ===== SUMMARY TABLE ===== */
.summary-wrapper {
    margin-bottom: 10px; /* smaller space */
}

.summary-title {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 4px;
    text-transform: uppercase;
}

/* summary table fit content */
.summary-table {
    width: auto !important;
    max-width: max-content;
    border-collapse: collapse;
}

.summary-table th,
.summary-table td {
    padding: 4px 6px !important;
    font-size: 13px !important; /* NEW: all summary table font = 13px */
    white-space: nowrap;        /* prevent unnecessary stretching */
}

.summary-table th {
    background-color: #e8e8e8;
    font-size: 13px !important;
}

.summary-table tbody tr:nth-child(even) {
    background-color: #fafafa;
}

.summary-table .total-row {
    background-color: #f0f0f0;
    font-weight: 800;
    font-size: 13px !important;
}

        /* ===== ROOM SECTIONS ===== */
        .rooms-wrapper {
            margin-top: 10px;
        }

        .rooms-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .room-section {
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 10px 10px 8px 10px;
            margin-bottom: 14px;
            page-break-inside: avoid !important;
            break-inside: avoid-page !important;
        }

        .room-header-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
        }

        .room-name {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .room-meta {
            font-size: 13px;
        }

        .room-table {
            width: 100%;
            margin-top: 4px;
        }

        .room-table th,
        .room-table td {
            font-size: 13px;
        }

        .room-table th:nth-child(1) { width: 45px; }   /* S.N. */
        .room-table th:nth-child(2) { width: 110px; }  /* Faculty */
        .room-table th:nth-child(4) { width: 70px; }   /* Total */

        .roll-numbers {
            font-size: 13px;
            line-height: 1.6;
            word-wrap: break-word;
            word-break: break-word;
        }

        .room-total-row td {
            background-color: #f0f0f0;
            font-weight: 800;
            font-size: 13px;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body {
                margin: 8mm;
                font-size: 12px;
            }

            .header-container {
                margin-bottom: 14px;
                padding-bottom: 8px;
            }

            .header-college {
                font-size: 17px;
            }

            .header-exam-title {
                font-size: 13px;
            }

            .header-time {
                font-size: 13px;
            }

            th, td {
                font-size: 12px;
                padding: 6px 7px;
            }

            .roll-numbers {
                font-size: 12px;
                line-height: 1.5;
            }

            .room-section {
                margin-bottom: 10px;
                page-break-inside: avoid !important;
                break-inside: avoid-page !important;
            }

            .mb-4 { margin-bottom: 14px; }
        }

        @page {
            size: A4;
            margin: 10mm;
        }
    </style>
</head>
<body onload="window.print()">

    {{-- ===== HEADER ===== --}}
    <div class="header-container">
        <div class="header-top">
            <div style="text-align:left; min-width:140px;">
                <div class="bold" style="font-size:13px;">Seat Plan</div>
                <div style="font-size:12px;">Generated: {{ now()->format('Y-m-d H:i') }}</div>
            </div>

            <div class="header-title-main">
                <div class="header-college">Purwanchal Campus</div>
                <div class="header-subtitle">Examination Section</div>
                <div class="header-exam-title">
                    Exam Seat Plan – {{ $exam?->exam_title }}
                </div>
                <div class="header-meta-line">
                    DATE: <span class="bold">{{ $examDate }}</span>
                    @if($exam?->semester)
                        &nbsp; | &nbsp; SEMESTER: <span class="bold">{{ $exam->semester }}</span>
                    @endif
                    @if($batch)
                        &nbsp; | &nbsp; BATCH: <span class="bold">{{ $batch == 1 ? 'New' : 'Old' }}</span>
                    @endif
                </div>
            </div>

            <div class="header-time">
                {{ $exam->start_time ? 'Time: ' . \Carbon\Carbon::parse($exam->start_time)->format('h:i A') : '' }}
            </div>
        </div>
    </div>

    @if(!$hasData)
        <p class="text-center" style="font-size: 16px; padding: 20px;">No data to display.</p>
    @else

        {{-- ===== SUMMARY TABLE ===== --}}
        @php
            $sumRegular = collect($summaryRows ?? [])->sum('regular');
            $sumBack    = collect($summaryRows ?? [])->sum('back');
            $sumTotal   = collect($summaryRows ?? [])->sum('total');
        @endphp

        @if(!empty($summaryRows))
            <div class="summary-wrapper">
                <div class="summary-title">Overall Summary</div>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Programme</th>
                            <th>Semester</th>
                            <th>Subject</th>
                            <th>Regular</th>
                            <th>Back</th>
                            <th>Total</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryRows as $idx => $row)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td>{{ $row['programme'] ?? '' }}</td>
                                <td class="text-center">{{ $row['semester'] ?? '' }}</td>
                                <td>{{ $row['subject'] ?? '' }}</td>
                                <td class="text-center">{{ $row['back'] ?? 0 }}</td>
                                <td class="text-center">{{ $row['regular'] ?? 0 }}</td>
                                <td class="text-center bold">{{ $row['total'] ?? 0 }}</td>
                                <td>{{ $row['remarks'] ?? '' }}</td>
                            </tr>
                        @endforeach

                        <tr class="total-row">
                            <td></td>
                            <td class="bold text-center">TOTAL</td>
                            <td></td>
                            <td></td>
                            <td class="text-center bold">{{ $sumBack }}</td>
                            <td class="text-center bold">{{ $sumRegular }}</td>
                            <td class="text-center bold">{{ $sumTotal }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ===== ROOM-WISE ROLL LIST ===== --}}
        <div class="rooms-wrapper">
            <div class="rooms-title">Room-wise Seat Plan</div>

            @foreach($roomSummaries as $roomId => $info)
                @php
                    $room = $info['room'];
                    $rows = $info['rows'];
                    $rowCount = count($rows);
                @endphp

                <div class="room-section">
                    <div class="room-header-row">
                        <div class="room-name">
                            Room: {{ $room->room_no }}
                        </div>
                        <div class="room-meta">
                            Total Students: <span class="bold">{{ $info['room_total'] }}</span>
                        </div>
                    </div>

                    <table class="room-table">
                        <thead>
                            <tr>
                                <th>S.N.</th>
                                <th>Faculty</th>
                                <th>Exam Roll No. (Symbol)</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                <tr>
                                    <td class="text-center bold">{{ $index + 1 }}</td>
                                    <td class="text-center">
                                        @if($row['faculty'])
                                            <span class="bold">{{ $row['faculty']->code }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="roll-numbers">
                                        {{ implode(', ', $row['rolls']) }}
                                    </td>
                                    <td class="text-center bold">{{ $row['total'] }}</td>
                                </tr>
                            @endforeach

                            <tr class="room-total-row">
                                <td colspan="3" class="text-right bold">TOTAL STUDENTS</td>
                                <td class="text-center bold">{{ $info['room_total'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

    @endif
</body>
</html>
