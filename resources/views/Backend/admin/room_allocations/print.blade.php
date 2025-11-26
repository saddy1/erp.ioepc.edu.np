<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Packets - {{ $exam->exam_title }} ({{ $examDate }})</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 18mm 15mm;
        }

        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
            color: #000;
        }

        .page {
            width: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        h1, h2, h3, h4 {
            margin: 0;
            padding: 0;
        }

        .letter-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .letter-header .college-name {
            font-size: 14px;
            font-weight: 700;
        }

        .letter-header .title {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: underline;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0 6px 0;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 10px;
        }

        th {
            text-align: center;
            font-weight: 700;
        }

        td {
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        .total-row td {
            font-weight: 700;
        }

        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }

        .signature-row {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .sig-block {
            width: 45%;
        }

        .sig-line {
            margin-top: 35px;
            border-top: 1px solid #000;
            padding-top: 3px;
        }
    </style>
</head>
<body>

{{-- =============== PAGE 1: SUMMARY LETTER =============== --}}
<div class="page">
    <div class="letter-header">
        {{-- You can replace this with full Nepali letterhead like your example --}}
        <div class="college-name">
            {{ config('app.college_name', 'ईन्जिनियरिङ्ग अध्ययन संस्था, पूर्वाञ्चल क्याम्पस, धरान') }}
        </div>
        <div style="margin-top:3px;">
            {{ $exam->exam_title }}
            (Batch: {{ $batch == 1 ? 'New' : 'Old' }})
        </div>
        <div class="title">
            उत्तर पुस्तिका पठाउने बारे&nbsp;।
        </div>
    </div>

    <div class="meta-row">
        <div>
            मिति (Date): <strong>{{ $examDate }}</strong>
        </div>
        <div>
            जम्मा विद्यार्थी (Total Students): <strong>{{ $totalPresent + $totalAbsent }}</strong>
        </div>
    </div>

    <p style="font-size:10px; text-align:justify; margin-bottom:8px;">
        तल उल्लेखित विषयहरूको उत्तर पुस्तिका सम्बन्धित विभाग / क्याम्पसमा पठाउँदा उपस्थित तथा अनुपस्थित
        विद्यार्थीको संख्या देहाय अनुसार रहेको व्यहोरा अनुरोध छ&nbsp;।
    </p>

    {{-- SUMMARY TABLE --}}
    <table>
        <thead>
        <tr>
            <th style="width:30px;">क्र.सं.</th>
            <th style="width:110px;">कार्यक्रम / Faculty</th>
            <th>विषय (Subject)</th>
            <th style="width:60px;">Subject Code</th>
            <th style="width:60px;">उपस्थित</th>
            <th style="width:60px;">अनुपस्थित</th>
        </tr>
        </thead>
        <tbody>
        @foreach($summaryRows as $row)
            <tr>
                <td class="text-center">{{ $row['sn'] }}</td>
                <td>
                    {{ $row['faculty_code'] }}<br>
                    <span style="font-size:9px;">{{ $row['faculty_name'] }}</span>
                </td>
                <td>{{ $row['subject_name'] }}</td>
                <td class="text-center">{{ $row['subject_code'] }}</td>
                <td class="text-center">{{ $row['present_total'] }}</td>
                <td class="text-center">{{ $row['absent_total'] }}</td>
            </tr>
        @endforeach

        {{-- TOTAL ROW --}}
        <tr class="total-row">
            <td></td>
            <td colspan="3" class="text-right">जम्मा (Total)</td>
            <td class="text-center">{{ $totalPresent }}</td>
            <td class="text-center">{{ $totalAbsent }}</td>
        </tr>
        </tbody>
    </table>

    <div class="signature-row">
        <div class="sig-block">
            <div>तयार गर्ने (Prepared By):</div>
            <div class="sig-line">नाम, हस्ताक्षर र मिति</div>
        </div>
        <div class="sig-block" style="text-align:right;">
            <div>सिफारिस गर्ने / स्वीकृत गर्ने (Approved By):</div>
            <div class="sig-line">नाम, हस्ताक्षर र मिति</div>
        </div>
    </div>
</div>

<div class="page-break"></div>

{{-- ===================================================== --}}
{{-- ========== FROM HERE: YOUR OLD PACKET PAGES ========== --}}
{{-- ===================================================== --}}

@foreach($packets as $packet)
    <div class="page">
        {{-- ⬇️ Put your existing single-packet layout here.
             Normally you already had something like:
             - Exam title, date, faculty, subject
             - Present list / Absent list, etc.
        --}}

        <h3 style="text-align:center; margin-bottom:6px;">
            Answer Script Packet – {{ $packet['subject_code'] }}
        </h3>
        <p style="font-size:10px; margin-bottom:4px;">
            Exam: {{ $packet['exam']->exam_title }}<br>
            Date: {{ $packet['exam_date'] }}<br>
            Faculty: {{ $packet['faculty']?->code }} – {{ $packet['faculty']?->name }}<br>
            Batch: {{ $packet['batch'] == 1 ? 'New' : 'Old' }}
        </p>

        <table class="mt-10">
            <thead>
            <tr>
                <th style="width:60px;">Status</th>
                <th>Symbol Numbers</th>
                <th style="width:80px;">Total</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="text-center">Present</td>
                <td>
                    @if(!empty($packet['present']))
                        {{ implode(', ', $packet['present']) }}
                    @else
                        —
                    @endif
                </td>
                <td class="text-center">{{ $packet['present_total'] }}</td>
            </tr>
            <tr>
                <td class="text-center">Absent</td>
                <td>
                    @if(!empty($packet['absent']))
                        {{ implode(', ', $packet['absent']) }}
                    @else
                        —
                    @endif
                </td>
                <td class="text-center">{{ $packet['absent_total'] }}</td>
            </tr>
            </tbody>
        </table>

        {{-- You can also include signature boxes here like you already had --}}
    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
