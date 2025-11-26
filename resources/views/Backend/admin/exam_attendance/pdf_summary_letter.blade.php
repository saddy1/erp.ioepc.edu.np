@extends('Backend.layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;700&display=swap');

    @page {
        size: A4;
        margin: 18mm 15mm;
    }

    body {
        font-family: "Noto Sans Devanagari", "Mangal", "Kalimati", Arial, sans-serif;
        color: #111;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .a4 {
        width: 190mm;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }

    .line {
        border-top: 1px solid #000;
        margin: 6px 0 12px;
    }

    /* ===== SCREEN STYLE ===== */
    @media screen {
        body {
            background: #f9fafb;
            padding: 24px;
        }

        .a4 {
            background: #fff;
            padding: 12mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
            border-radius: 8px;
        }

        .printbar {
            display: flex;
            justify-content: end;
            gap: 8px;
            margin-bottom: 16px;
        }

        button {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            cursor: pointer;
        }

        button:hover {
            background: #f3f4f6;
        }
    }

    /* ===== PRINT STYLE ===== */
    @media print {
        .printbar,
        header,
        nav,
        aside,
        .sidebar,
        .navbar,
        .footer,
        .app-header,
        .app-footer {
            display: none !important;
        }

        body * {
            visibility: hidden !important;
        }

        .a4,
        .a4 * {
            visibility: visible !important;
        }

        .a4 {
            position: absolute;
            left: 0;
            top: 0;
            margin: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            width: 190mm;
        }
    }

    .text-center { text-align: center; }
    .mb-2 { margin-bottom: 8px; }
    .mb-4 { margin-bottom: 16px; }
    .mt-2 { margin-top: 8px; }
    .font-bold { font-weight: bold; }
    .underline { text-decoration: underline; }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    th, td {
        border: 1px solid #000;
        padding: 4px 6px;
        vertical-align: middle;
    }

    th {
        font-weight: bold;
        background: #f3f3f3;
        text-align: center;
    }

    .no-border {
        border: 0 !important;
    }
</style>

<!-- Print button (screen only) -->
<div class="printbar">
    <button onclick="window.print()">🖨️ Print / Save as PDF</button>
</div>

<!-- Printable content -->
<div class="a4" id="printable">
    <!-- Header -->
    <div class="text-center mb-4" style="line-height: 1.4;">
        <div class="font-bold" style="font-size: 15px;">त्रिभुवन विश्वविद्यालय</div>
        <div style="font-size: 13px;">इन्जिनियरिङ अध्ययन संस्थान</div>
        <div style="font-size: 13px;">पूर्वाञ्चल क्याम्पस, धरान</div>
        
    </div>
    {{-- Official Letter Heading --}}
<div style="font-size: 13px; margin-bottom: 10px;">
    <strong>च.नं. ____________________ <br> प.सं. ____________________</strong>
</div>
<div style="font-size: 13px; margin-bottom: 12px; text-align: right;">
    मिति: {{ $examDate }}
</div>

<div style="font-size: 13px; line-height: 1.6;">
    श्रीमान् सहायक डीनज्यू<br>
    परीक्षा नियन्त्रण महाशाखा<br>
    इन्जिनियरिङ अध्ययन संस्थान<br>
    चाकुपाट, ललितपुर
</div>



<div style="font-weight: bold; font-size: 14px; margin-bottom: 6px; text-align: center;">
    विषयः उत्तर पुस्तिका पठाइएको बारे ।
</div>



<p style="font-size: 13px; line-height: 1.7; text-align: justify;">
    उपरोक्त सम्बन्धमा पूर्वाञ्चल क्याम्पस केन्द्रमा सञ्चालित 
    बी.ई./ बी.आर्क. / स्नाताकोत्तर 
    प्रथम, दोस्रो, तेस्रो, चौथो वर्ष 
    प्रथम, दोस्रो खण्ड नियमित / पुनः परीक्षाको 
    उत्तर पुस्तिका निम्नबमोजिम सिलबन्दी गरी पठाइएको व्यहोरा अनुरोध छ ।
</p>



    <!-- Main table -->
    <table>
        <thead>
        <tr>
            <th style="width: 6%;">क्र. सं.</th>
            <th style="width: 26%;">संकाय / Faculty</th>
            <th style="width: 12%;">वर्ष / Year<br>भाग / Part</th>
            <th style="width: 30%;">विषय / Subject</th>
            <th style="width: 8%;">उपस्थित<br>Present</th>
            <th style="width: 8%;">अनुपस्थित<br>Absent</th>
            <th style="width: 10%;">जम्मा<br>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row['faculty_code'] }} — {{ $row['faculty_name'] }}</td>
                <td class="text-center">{{ $row['year_part'] ?? '—' }}</td>
                <td>{{ $row['subject_code'] }} — {{ $row['subject_name'] }}</td>
                <td class="text-center">{{ $row['present'] }}</td>
                <td class="text-center">{{ $row['absent'] }}</td>
                <td class="text-center">{{ $row['total'] }}</td>
            </tr>
        @endforeach

        <!-- Grand total row -->
        <tr>
            <td colspan="4" class="font-bold text-center">
                जम्मा (Grand Total)
            </td>
            <td class="font-bold text-center">{{ $grandPresent }}</td>
            <td class="font-bold text-center">{{ $grandAbsent }}</td>
            <td class="font-bold text-center">{{ $grandTotal }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <div style="margin-top: 40px;">
        <table>
            <tr>
                <td class="no-border" style="width: 33%; text-align: left;">
                    <span class="font-bold">तयार गर्ने (Prepared by):</span>
                    <br><br>
                    ______________________
                </td>
                <td class="no-border" style="width: 33%; text-align: center;">
                    <span class="font-bold">जाँच गर्ने (Checked by):</span>
                    <br><br>
                    ______________________
                </td>
                <td class="no-border" style="width: 33%; text-align: right;">
                    <span class="font-bold">स्वीकृत गर्ने (Approved by):</span>
                    <br><br>
                    ______________________
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection