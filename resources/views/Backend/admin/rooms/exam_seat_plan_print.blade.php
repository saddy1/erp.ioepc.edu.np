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
            font-size: 12px; 
            margin: 20px;
            line-height: 1.3;
            color: #333;
        }
        
        table { 
            border-collapse: collapse; 
            width: 100%;
            margin-bottom: 20px;
        }
        
        th, td { 
            border: 1.5px solid #000; 
            padding: 6px 8px;
            font-size: 11px;
        }
        
        th { 
            text-align: center;
            background-color: #f5f5f5;
            font-weight: 600;
            font-size: 12px;
        }
        
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 25px; }
        .bold { font-weight: 700; }

        /* Header Styles */
        .header-container {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            border-bottom: 2px solid #333;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .header-info {
            font-size: 15px;
            color: #555;
        }

        /* Summary table — keep same */
        .summary-table th {
            background-color: #e8e8e8;
        }
        .summary-table .total-row {
            background-color: #f9f9f9;
            font-weight: 700;
        }

        /* ======================= ROOM WISE LIST — BIGGER FONT ======================= */
        .room-table th,
        .room-table td {
            font-size: 14px !important;
            padding: 8px 10px !important;
        }

        .roll-numbers {
            font-size: 14px !important;
            line-height: 1.8 !important;
            word-wrap: break-word;
        }

        .room-total-row td {
            background-color: #f0f0f0;
            font-size: 15px !important;
            font-weight: 700 !important;
        }
        /* ========================================================================== */

        /* Print Styles */
        @media print {
            body {
                margin: 10px;
                font-size: 11px;
            }
            
            table {
                page-break-inside: auto;
            }
            
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            
            .header-container {
                margin-bottom: 15px;
                padding: 10px;
            }
            
            .header-title {
                font-size: 16px;
                margin-bottom: 8px;
            }
            
            .header-info {
                font-size: 12px;
            }

            /* Print version — slightly smaller but still bigger than before */
            .room-table th,
            .room-table td {
                font-size: 12px !important;
                padding: 6px 8px !important;
            }

            .roll-numbers {
                font-size: 12px !important;
                line-height: 1.6 !important;
            }

            .room-total-row td {
                font-size: 13px !important;
            }

            .mb-4 { margin-bottom: 15px; }
        }

        @page {
            size: A4;
            margin: 1cm;
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Header --}}
    <div class="header-container">
        <div class="header-title">EXAM SEAT PLAN – {{ $exam?->exam_title }}</div>
        <div class="header-info">
            DATE: {{ $examDate }}
            @if($exam?->semester)
                &nbsp; | &nbsp; Semester: {{ $exam->semester }}
            @endif
            @if($batch)
                &nbsp; | &nbsp; Batch: {{ $batch == 1 ? 'New' : 'Old' }}
            @endif
        </div>
    </div>

    @if(!$hasData)
        <p class="text-center" style="font-size: 16px; padding: 20px;">No data to display.</p>
    @else

        {{-- ================= SUMMARY TABLE ================= --}}
        @php
            $sumRegular = collect($summaryRows ?? [])->sum('regular');
            $sumBack    = collect($summaryRows ?? [])->sum('back');
            $sumTotal   = collect($summaryRows ?? [])->sum('total');
        @endphp

        @if(!empty($summaryRows))
           <table class="summary-table mb-4">
    <thead>
        <tr>
            <th>S.N.</th>
            <th>Programme</th>
            <th>Semester</th>
            <th>Subject</th>
            <th>Back</th>
            <th>Regular</th>
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
                <td class="text-center">{{ $row['total'] ?? 0 }}</td>
                <td>{{ $row['remarks'] ?? '' }}</td>
            </tr>
        @endforeach

        <tr class="total-row">
            <td></td>
            <td class="bold text-center">TOTAL</td>
            <td></td>
            <td></td>
            <td class="text-center bold">{{ $sumRegular }}</td>
            <td class="text-center bold">{{ $sumBack }}</td>
            <td class="text-center bold">{{ $sumTotal }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

        @endif

        {{-- ================= ROOM-WISE ROLL LIST ================= --}}
        <table class="room-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Room No.</th>
                    <th style="width: 100px;">Faculty</th>
                    <th>Exam Roll No. (Symbol)</th>
                    <th style="width: 70px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roomSummaries as $roomId => $info)
                    @php
                        $room = $info['room'];
                        $rows = $info['rows'];
                    @endphp

                    @foreach($rows as $row)
                        <tr>
                            <td class="text-center"><strong>{{ $room->room_no }}</strong></td>
                            <td class="text-center">
                                @if($row['faculty'])
                                    <strong>{{ $row['faculty']->code }}</strong>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="roll-numbers">{{ implode(', ', $row['rolls']) }}</td>
                            <td class="text-center"><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @endforeach

                    <tr class="room-total-row">
                        <td colspan="3" class="text-center bold">ROOM {{ $room->room_no }} TOTAL</td>
                        <td class="text-center bold">{{ $info['room_total'] }}</td>
                    </tr>

                @endforeach
            </tbody>
        </table>

    @endif
</body>
</html>
