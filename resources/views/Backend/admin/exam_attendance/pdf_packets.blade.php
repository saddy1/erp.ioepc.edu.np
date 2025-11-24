<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Exam Packets - {{ $examDate }}</title>
    <style>
        @page {
            margin: 20mm 20mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* University Logo/Header */
        .top-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            gap: 20px;
        }

        .logo-star {
            font-size: 50px;
            font-weight: bold;
            line-height: 1;
        }

        .header-text {
            text-align: center;
        }

        /* Main Header Box */
        .header-section {
            border: 2px solid #000;
            padding: 12px;
            margin-bottom: 15px;
        }

        .header-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .main-heading {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        .sub-heading {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
            text-decoration: underline;
        }

        /* Meta Information Table */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }

        .info-left {
            flex: 1;
        }

        .info-right {
            flex: 0 0 35%;
            text-align: right;
        }

        .filled-value {
            font-weight: bold;
            font-size: 12px;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 100px;
        }

        .info-text {
            margin: 8px 0;
            font-size: 10px;
        }

        .note-text {
            margin-top: 10px;
            font-size: 10px;
            line-height: 1.4;
        }

        /* Signature Section - Table Format */
        .signature-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .signature-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 10px;
            padding: 0 10px;
        }

        .signature-line {
            border-bottom: 1px dotted #000;
            height: 40px;
            margin-bottom: 5px;
        }

        /* Second Page Section */
        .form-section {
            border: 2px solid #000;
            padding: 0;
            margin-bottom: 15px;
        }

        .section-header {
            font-size: 11px;
            font-weight: normal;
            padding: 8px 10px;
            border-bottom: 1px solid #000;
        }

        .content-area {
            min-height: 420px;
            padding: 12px;
            position: relative;
            font-size: 11px;
            line-height: 1.8;
            font-weight: bold;
        }

        .content-area-small {
            min-height: 280px;
            padding: 12px;
            position: relative;
            font-size: 11px;
            line-height: 1.8;
            font-weight: bold;
        }

        .total-line {
            position: absolute;
            bottom: 150px;
            right: 15px;
            font-size: 17px;
        }
        .total-present {
            position: absolute;
            bottom: 480px;
            right: 15px;
            font-size: 17px;
            font-weight: bold;
        }

        .roll-numbers {
            word-wrap: break-word;
        }

        /* Footer Notes */
        .footer-notes {
            margin-top: 15px;
            font-size: 9px;
            line-height: 1.5;
        }

        .footer-notes p {
            margin: 8px 0;
        }

        .nb-label {
            font-weight: bold;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .header-table th,
        .header-table td {
            padding: 6px;
            font-size: 10px;
            text-align: center;
        }

        .header-table th {

            font-weight: bold;
        }

        .header-table {
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
    </style>
</head>

@php
    $logoPath = public_path('assets/ioepc_logo.png');
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = 'data:image/png;base64,' . $logoData;
@endphp

<body>

    @foreach ($packets as $idx => $p)
        {{-- PAGE 1: DETAILS OF CONTENT OF THE PACKET --}}
        @if ($idx > 0)
            <div class="page-break"></div>
        @endif
        <table class="header-table">
            <thead>
                <tr>
                    <th>
                        <div class="university-logo">
                            <div class="logo-star">
                                <img src="{{ $logoSrc }}" alt="IOE PC Logo" style="max-height: 100px;">
                            </div>
                        </div>
                    </th>
                    <th>
                        <div class="header-title" style="text-align: center">
                            <span style="font-size: 13px">TRIBHUVAN UNIVERSITY<br>
                                INSTITUTE OF ENGINEERING<br></span>

                            EXAMINATION CONTROL DIVISION
                        </div>
                    </th>
                    <th style="width: 100px"></th>
                </tr>
            </thead>

        </table>
        <div class="main-heading">
            DETAILS OF CONTENT OF THE PACKET
        </div>
        <div class="sub-heading">
            To be filled in Examination Centre
        </div>

        <div class="header-section">





            <div class="info-row">
                <div class="info-left">
                    Serial No. <span class="dotted-line" style="width: 150px;"></span>
                </div>
                <div class="info-right"></div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Centre :-  <span style="width: 200px; font-weight: bold;">IOE Purwanchal Campus Dharan</span>
                </div>
                <div class="info-right">
                    Date :- <span style="font-weight: bold">{{ $p['exam_date']}}</span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Examination :- <span style="font-weight: bold">{{ $p['exam']->exam_title }}</span>
                </div>
                <div class="info-right">
                    Year :- <span style="font-weight: bold">{{ $p['batch'] == 1 ? 'I' : 'II' }}</span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Subject :- <span style=" font-weight: bold;">{{ $p['subject_code'] }} - {{ $p['faculty']->name ?? '' }}</span>
                </div>
                <div class="info-right">
                    Paper <span  style="width: 30px;border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 50px; font-weight: bold;;"></span>
                </div>
            </div>

            <div class="info-text">
                Total Number of Answer books in the packet :-  <span  style="font-weight: bold">{{ $p['present_total'] }}</span>
            </div>

            <div class="note-text">
                Roll Nos. are noted on the back of this form (a)
            </div>

            <table class="signature-table">
                <thead>
                    <tr>
                        <th>Written By</th>
                        <th>CHECKED BY<br>(ASST-SUPERINTENDENT)</th>
                        <th>SUPERINTENDENT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>

                            .......................................<br>

                        </td>
                        <td>.......................................</td>
                        <td>.......................................</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="header-section" style="border-top: 2px solid #000; padding-top: 15px;">
            <div class="sub-heading">
                To be filled in Examination Centre
            </div>

            <div class="info-row">
                <div class="info-left">
                    Code No. <span class="dotted-line" style="width: 200px;"></span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Examiner's Name <span class="dotted-line" style="width: 300px;"></span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Date of Dispatch <span class="dotted-line" style="width: 250px;"></span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-left">
                    Date of Receipt <span class="dotted-line" style="width: 250px;"></span>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <strong>Remarks</strong> <span class="dotted-line" style="width: 90%;"></span>
            </div>
            <div style="margin-top: 5px;">
                <span class="dotted-line" style="width: 100%;"></span>
            </div>
            <div style="margin-top: 5px;">
                <span class="dotted-line" style="width: 100%;"></span>
            </div>
        </div>

        <div class="footer-notes">
            <p>
                <span class="nb-label">N.B. 1.</span>
                The Examiner immediately on receipt of the packet should verify the content with the above lists. If any
                paper mentioned in the list be not found in the packet, the Asst. Dean, Examination Control Division may
                be immediately informed of it. If any extra paper be found it should be sent at once to the Asst. Dean,
                Examination Control Division.
            </p>
            <p>
                <span class="nb-label">2.</span>
                The Examiner is requested to attach his Remuneration bill with it to facilitate checking, otherwise
                payment will be delayed.
            </p>
        </div>

        {{-- PAGE 2: ROLL NUMBERS --}}
        <div class="page-break"></div>

        <div style="text-align: right; font-size: 10px; margin-bottom: 10px;">
            Contd...2
        </div>

        {{-- Present Students Box --}}
        <div class="form-section">
            <div class="section-header">
                (a) Roll Nos. of Candidates whose answer books are enclosed in the packet
            </div>
            <div class="content-area">
                <div class="roll-numbers">
                    @php
                        $presentChunks = array_chunk($p['present'], 10);
                    @endphp
                    @foreach ($presentChunks as $chunk)
                        {{ implode(', ', $chunk) }}@if (!$loop->last)
                            ,
                        @endif
                        <br>
                    @endforeach
                   
                   
                </div>
                
              
                
            </div>
             <div class="total-present"> Total :- {{ $p['present_total'] }}</div>
              
            
        </div>

        {{-- Absentees Box --}}
        <div class="form-section">
            <div class="section-header">
                Roll Nos. of Absentees
            </div>
            <div class="content-area-small">
                <div class="roll-numbers">
                    @php
                        $absentChunks = array_chunk($p['absent'], 10);
                    @endphp
                    @if (!empty($p['absent']))
                        @foreach ($absentChunks as $chunk)
                            {{ implode(', ', $chunk) }}@if (!$loop->last)
                                ,
                            @endif
                            <br>
                        @endforeach
                    @endif
                </div>
                <div class="total-line">
                    Total :- {{ $p['absent_total'] }}
                </div>
            </div>
        </div>
    @endforeach

</body>

</html>
