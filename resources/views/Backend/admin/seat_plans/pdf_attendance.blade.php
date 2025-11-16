<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Sheets - {{ $examDate }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; padding: 12px; }

        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }

        .header {
            text-align: center;
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
            background: #f5f5f5;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .info-table { width: 100%; margin: 8px 0; border-collapse: collapse; }
        .info-table td { padding: 5px 8px; font-size: 11px; border: 1px solid #999; }
        .info-label { font-weight: bold; width: 130px; background: #e8e8e8; }
        .info-value { border-bottom: 1px solid #000; }

        .subject-header {
            background: #333;
            color: #fff;
            padding: 7px;
            margin: 8px 0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            border: 2px solid #000;
        }

        .attendance-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
        .attendance-table th, .attendance-table td { border: 1px solid #000; padding: 6px 4px; }
        .attendance-table th {
            background: #d0d0d0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        .col-sn { width: 35px; text-align: center; }
        .col-roll { width: 85px; }
        .col-symbol { width: 85px; text-align: center; font-family: "Courier New", monospace; font-weight: bold; }
        .col-name { width: auto; text-transform: uppercase; }
        .col-copy { width: 75px; }
        .col-sign { width: 85px; }
        .col-remarks { width: 95px; }

        .attendance-table tbody tr { height: 26px; }

        .footer-section { 
            margin-top: 12px; 
            border-top: 2px solid #000; 
            padding-top: 10px; 
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .signatures-column {
            flex: 1;
        }

        .signature-item {
            margin-bottom: 8px;
            font-size: 11px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .signature-dots {
            border-bottom: 1px solid #000;
            min-width: 250px;
            display: inline-block;
            height: 20px;
        }

        .page-footer { 
            text-align: center; 
            font-size: 9px; 
            color: #666; 
            margin-top: 10px; 
            padding-top: 8px; 
            border-top: 1px solid #ccc; 
        }
    </style>
</head>
<body>
@foreach($sheets as $sheet)
    <div class="sheet">
        <div class="header">
            <h1>Student Attendance Sheet</h1>
        </div>

        <table class="info-table">
            <tr>
                <td class="info-label">Examination:</td>
                <td class="info-value" colspan="3"><strong>{{ $exam->exam_title }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Room Number:</td>
                <td class="info-value"><strong>{{ $sheet['room']->room_no }}</strong></td>
                <td class="info-label">Exam Date:</td>
                <td class="info-value"><strong>{{ $examDate }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Start Time:</td>
                <td class="info-value">
                    @if($exam->start_time)
                        @php
                            try {
                                echo \Carbon\Carbon::parse($exam->start_time)->format('h:i A');
                            } catch (\Exception $e) {
                                echo $exam->start_time;
                            }
                        @endphp
                    @else
                        N/A
                    @endif
                </td>
                <td class="info-label">End Time:</td>
                <td class="info-value">
                    @if($exam->end_time)
                        @php
                            try {
                                echo \Carbon\Carbon::parse($exam->end_time)->format('h:i A');
                            } catch (\Exception $e) {
                                echo $exam->end_time;
                            }
                        @endphp
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </table>

        <div class="subject-header">
            FACULTY: {{ strtoupper($sheet['faculty_name']) }} |
            SUBJECT: {{ strtoupper($sheet['subject_name']) }} ({{ $sheet['subject_code'] }})
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-sn">S.N.</th>
                    <th class="col-roll">Roll No.</th>
                    <th class="col-symbol">Symbol No.</th>
                    <th class="col-name">Student Name</th>
                    <th class="col-copy">1st Copy No.</th>
                    <th class="col-sign">Signature</th>
                    <th class="col-remarks">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sheet['students'] as $student)
                    <tr>
                        <td class="col-sn">{{ $student['sn'] }}</td>
                        <td class="col-roll">{{ $student['roll_no'] }}</td>
                        <td class="col-symbol">{{ $student['symbol_no'] }}</td>
                        <td class="col-name">{{ strtoupper($student['student_name']) }}</td>
                        <td class="col-copy"></td>
                        <td class="col-sign"></td>
                        <td class="col-remarks"></td>
                    </tr>
                @endforeach

                {{-- ensure min 3 rows for spacing --}}
                @if(count($sheet['students']) < 3)
                    @for($i = count($sheet['students']); $i < 3; $i++)
                        <tr>
                            <td class="col-sn">{{ $i + 1 }}</td>
                            <td class="col-roll"></td>
                            <td class="col-symbol"></td>
                            <td class="col-name"></td>
                            <td class="col-copy"></td>
                            <td class="col-sign"></td>
                            <td class="col-remarks"></td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px; font-size: 11px; background: #e8e8e8;">Total Students</th>
                    <th style="border: 1px solid #000; padding: 8px; font-size: 11px; background: #e8e8e8;">Present</th>
                    <th style="border: 1px solid #000; padding: 8px; font-size: 11px; background: #e8e8e8;">Absent</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold; font-size: 14px;">
                        {{ $sheet['total_students'] }}
                    </td>
                    <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                    </td>
                    <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="footer-section">
            <!-- Left: Invigilators -->
            <div class="signatures-column">
                @foreach($sheet['invigilators'] as $index => $inv)
                    <div class="signature-item">
                        <div class="signature-label">
                            {{ $index + 1 }}. Invigilator: {{ $inv->full_name }} 
                        </div>
                        <span class="signature-dots"></span>
                    </div>
                @endforeach

                @if(count($sheet['invigilators']) < 2)
                    @for($i = count($sheet['invigilators']); $i < 2; $i++)
                        <div class="signature-item">
                            <div class="signature-label">
                                {{ $i + 1 }}. Invigilator: .......................................
                            </div>
                            <span class="signature-dots"></span>
                        </div>
                    @endfor
                @endif
            </div>
        </div>

        <div class="page-footer">
            Room: {{ $sheet['room']->room_no }} | 
            Faculty: {{ $sheet['faculty_name'] }} | 
            Subject: {{ $sheet['subject_code'] }} | 
            Generated: {{ date('d/m/Y h:i A') }}
        </div>
    </div>
@endforeach
</body>
</html>