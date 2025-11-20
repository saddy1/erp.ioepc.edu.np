<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Attendance Sheets - {{ $examDate }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin-left: 18mm;
            margin-right: 18mm;
            margin-top: 10mm;
            margin-bottom: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 0;
            margin: 0;
        }

        .sheet {
            page-break-after: always;
            page-break-inside: avoid;
            height: 277mm;
            display: flex;
            flex-direction: column;
            padding: 8px 15px;
            /* extra inner margin also */
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .room-number {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }
        .room-text {
            font-size: 20px;
        }

        .header {
            text-align: center;
            padding: 4px;
            margin-bottom: 4px;
        }

        .header h1 {
            font-size: 16px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .header h3 {
            font-size: 13px;
            margin-bottom: 2px;
        }

        .header h4 {
            font-size: 12px;
            margin-bottom: 2px;
        }

        .header-title {
            font-size: 13px;
            font-weight: bold;
        }

        .info-table {
            width: 100%;
            margin: 4px 0;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 6px;
            font-size: 13px;
            border: 1px solid #999;
        }

        .info-label {
            font-weight: bold;
            width: 120px;
            background: #e8e8e8;
        }

        .faculty-label {
            color: #000;
            padding: 5px;
            margin: 4px 0;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            border: 1px solid #000;
        }

        .info-value {
            border-bottom: 1px solid #000;
        }

        .subject-header {
            background: #fff;
            color: #000;
            padding: 5px;
            margin: 4px 0;
            font-weight: bold;
            font-size: 13px;
            text-align: center;
            border: 1px solid #000;

        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: 13px;
            flex-grow: 1;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-weight: bold;
            font-size: 13px;
        }

        .attendance-table th {
            background: #d0d0d0;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
        }

        .col-sn {
            width: 30px;
            text-align: center;
            font-size: 13px;
        }

        .col-roll {
            width: 75px;
        }

        .col-symbol {
            width: 75px;
            text-align: center;
            font-family: "Courier New", monospace;
            font-weight: bold;
            font-size: 13px;
        }

        .col-name {
            width: 150px;
            font-family: "Courier New", monospace;
            font-weight: bold;
            font-size: 13px;
        }

        .col-copy {
            width: 70px;
        }

        .col-sign {
            width: 75px;
        }

        .col-remarks {
            width: 85px;
        }

        .attendance-table tbody tr {
            height: 22px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
            text-align: center;
        }

        .summary-table th {
            background: #e8e8e8;
            font-weight: bold;
        }

        .summary-table td {
            font-weight: bold;
            font-size: 12px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .signature-table th,
        .signature-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
            text-align: center;
        }

        .signature-table th {
            background: #e8e8e8;
            font-weight: bold;
        }

        .page-footer {
            text-align: center;
            font-size: 8px;
            color: #666;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #ccc;
        }

        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .sheet {
                page-break-after: always;
                page-break-inside: avoid;
            }

            .sheet:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>
    @foreach ($sheets as $sheet)
        
        <div class="sheet">
            <div class="page-count" style="text-align:left; font-size:10px; margin-bottom:4px;">
            Page {{ $sheet['page_no'] }}/{{ $sheet['page_total'] }}
        </div>
            <div class="room-number">
                ROOM NO: <span class="room-text">{{ $sheet['room']->room_no }}</span>
            </div>

            <div class="header">
                <h1>TRIBHUVAN UNIVERSITY</h1>
                <h3>INSTITUTE OF ENGINEERING</h3>
                <h4>EXAMINATION CONTROL DIVISION</h4>
                <div class="header-title">STUDENTS' ATTENDANCE</div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="info-label">Examination:</td>
                    <td class="info-value" colspan="1"><strong>{{ $exam->exam_title }}</strong></td>
                    <td class="info-label">Faculty</td>
                    <td class="faculty-label">{{ $sheet['faculty_code'] }}</td>


                </tr>
                <tr>
                    <td class="info-label">Exam Center:</td>
                    <td class="info-value"><strong>Purwanchal Campus, Dharan</strong></td>
                    <td class="info-label">Exam Date:</td>
                    <td class="info-value"><strong>{{ $examDate }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Start Time:</td>
                    <td class="info-value">
                        @if ($exam->start_time)
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
                    <td class="info-label">Year/Part</td>
                    <td class="info-value">
                        @if ($exam->semester)
                            {{ $semester }}
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
                        <th class="col-symbol">Symbol No.</th>
                        <th class="col-name">Student Name</th>
                        <th class="col-copy">1st Copy No.</th>
                        <th class="col-sign">2nd Copy No.</th>
                        <th class="col-remarks">Signature</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet['students'] as $student)
                        <tr>
                            <td class="col-sn">{{ $student['sn'] }}</td>
                            <td class="col-symbol">{{ $student['symbol_no'] }}</td>
                            <td class="col-name">{{ strtoupper($student['student_name']) }}</td>
                            <td class="col-copy"></td>
                            <td class="col-sign"></td>
                            <td class="col-remarks"></td>
                        </tr>
                    @endforeach

                    {{-- ensure min 3 rows for spacing --}}
                    @if (count($sheet['students']) < 3)
                        @for ($i = count($sheet['students']); $i < 3; $i++)
                            <tr>
                                <td class="col-sn">{{ $i + 1 }}</td>
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

            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Total Students</th>
                        <th>Present</th>
                        <th>Absent</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $sheet['total_students'] }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <table class="signature-table">
                <thead>
                    <tr>
                        <th>INVIGILATOR</th>
                        <th>CHECKED BY<br>(ASST-SUPERINTENDENT)</th>
                        <th>SUPERINTENDENT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            @foreach ($sheet['invigilators'] as $index => $inv)
                                {{ $index + 1 }}. {{ $inv->full_name }}
                                .......................................<br>
                            @endforeach
                        </td>
                        <td>.......................................</td>
                        <td>.......................................</td>
                    </tr>
                </tbody>
            </table>

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
