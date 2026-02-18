@extends('layouts.main')

@section('title', 'Student Evaluation Summary')

@section('breadcrumb')
<ol class="breadcrumb breadcrumb-custom">
    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.list_student') }}">Student List</a></li>
    <li class="breadcrumb-item active">Evaluation Summary</li>
</ol>
@endsection

@section('styles')
<style>

    /* Ensure card body can expand */
    .card-body {
        overflow: visible !important;
        min-height: auto !important;
    }

    #evaluation-document {
        overflow: visible;
    }

    .report-card-container {
        background: white;
        max-width: 8.5in;
        margin: 0 auto;
        padding: 0.5in;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
        min-height: auto;
        height: auto;
    }

    .watermark-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.05;
        width: 500px;
        height: 500px;
        z-index: 0;
        pointer-events: none;
    }

    .content {
        position: relative;
        z-index: 1;
        min-height: auto;
        height: auto;
    }

    .header {
        border-bottom: 8px solid #2a347e;
        padding-bottom: 15px;
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .header-left {
        flex-shrink: 0;
    }

    .logo {
        width: 80px;
        height: 80px;
    }

    .header-right {
        flex: 1;
        text-align: left;
        padding-top: 5px;
    }

    .school-name {
        font-size: 16px;
        font-weight: bold;
        margin: 0 0 3px 0;
        letter-spacing: 1px;
    }

    .school-info {
        font-size: 12px;
        margin: 1px 0;
        line-height: 1.3;
    }

    .report-title {
        font-size: 14px;
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 3px;
        letter-spacing: 3px;
        text-align: center;
    }

    .school-year {
        font-size: 11px;
        margin: 2px 0;
        text-align: center;
    }

    .student-info-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    .student-info-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        font-size: 12px;
    }

    .info-label {
        font-weight: bold;
        width: 140px;
        background: #f8f9fa;
    }

    .info-value {
        padding: 6px 8px;
    }

    .grades-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin-bottom: 0;
    }

    .grades-table th {
        border: 1px solid #000;
        padding: 8px 5px;
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        line-height: 1.2;
    }

    .grades-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: middle;
    }

    .grades-table tbody tr:nth-child(even) {
        background: #f8f9fa;
    }

    .category-header {
        background: #2a347e;
        color: white;
        font-weight: bold;
        text-align: left;
        padding: 5px 8px !important;
    }

    .footer-section {
        margin-top: 30px;
        display: flex;
        justify-content: center;
    }

    .footer-box {
        padding: 10px;
        display: inline-block;
    }

    .footer-title {
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .footer-row {
        font-size: 12px;
        padding: 2px 0;
        white-space: nowrap;
    }

    .semester-section {
        margin: 25px 0;
        page-break-inside: avoid;
        min-height: auto;
        height: auto;
    }

    #evaluation-semesters-container {
        min-height: auto;
        height: auto;
        overflow: visible;
    }

    .semester-header {
        background: #2a347e;
        color: white;
        padding: 10px 15px;
        font-weight: bold;
        font-size: 13px;
        margin-bottom: 10px;
        text-align: center;
        letter-spacing: 1px;
    }

    @media (max-width: 767.98px) {
        .report-card-container {
            padding: 0.25in;
            margin: 10px;
        }

        .logo {
            width: 60px;
            height: 60px;
        }

        .school-name {
            font-size: 14px;
        }

        .school-info {
            font-size: 10px;
        }

        .grades-table {
            font-size: 10px;
        }

        .grades-table th,
        .grades-table td {
            padding: 4px 3px;
        }

        .footer-section {
            flex-direction: column;
            align-items: center;
        }
    }

    @media print {
        @page {
            size: letter portrait;
        }

        /* Hide all chrome UI */
        .no-print,
        .main-header,
        .main-sidebar,
        .main-footer,
        .breadcrumb-custom,
        ol.breadcrumb {
            display: none !important;
        }

        body {
            margin: 0;
            padding: 0;
            background: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Strip all wrapper/layout chrome */
        .content-wrapper,
        .wrapper,
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
        }

        /* Remove AdminLTE card borders — card-outline adds border-top: 3px solid */
        .card,
        .card-outline,
        .card-outline.card-primary,
        .card-primary {
            border: none !important;
            border-top: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .report-card-container {
            margin: 0;
            padding: 0;
            box-shadow: none !important;
            max-width: 100%;
            width: 100%;
        }

        .semester-section {
            page-break-inside: avoid;
        }

        /* Footer row alignment */
        .footer-section {
            display: flex !important;
            justify-content: center !important;
            flex-direction: row !important;
        }

        .footer-box {
            display: inline-block !important;
        }

        /* Ensure colored backgrounds print */
        .category-header {
            background: #2a347e !important;
            color: white !important;
        }

        .semester-header {
            background: #2a347e !important;
            color: white !important;
        }

        .grades-table tbody tr:nth-child(even) {
            background: #f8f9fa !important;
        }

        .info-label {
            background: #f8f9fa !important;
        }
    }
</style>
@endsection

@section('content')
<br>
<div class="container-fluid">

    <!-- Evaluation Document -->

            <div id="evaluation-document">
                <div class="report-card-container">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" class="watermark-logo" alt="Watermark">
                    
                    <div class="content">
                        <!-- Header -->
                        <div class="header">
                            <div class="header-left">
                                <img src="{{ asset('img/logo/trinity_logo.png') }}" class="logo" alt="School Logo">
                            </div>
                            <div class="header-right">
                                <div class="school-name">TRINITY POLYTECHNIC COLLEGE INC.</div>
                                <div class="school-info">Goldstar Bldg., QX33+QJ2, Marilao, 3019 Bulacan</div>
                                <div class="school-info">Telephone Number: 0947 364 6906</div>
                                <div class="school-info">Email: trinitybulacan@gmail.com</div>
                            </div>
                        </div>

                        <div class="report-title">STUDENT EVALUATION SUMMARY</div>
                        <div class="school-year">Complete Academic Record</div>

                        <!-- Student Information -->
                        <div class="student-info">
                            <table class="student-info-table">
                                <tr>
                                    <td class="info-label">NAME:</td>
                                    <td class="info-value" colspan="3">{{ strtoupper($student->last_name) }}, {{ strtoupper($student->first_name) }} {{ strtoupper($student->middle_name) }}</td>
                                </tr>
                                <tr>
                                    <td class="info-label">STUDENT NUMBER:</td>
                                    <td class="info-value">{{ $student->student_number }}</td>
                                    <td class="info-label">STUDENT TYPE:</td>
                                    <td class="info-value">{{ strtoupper($student->student_type) }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Grades by Semester -->
                        <div id="evaluation-semesters-container">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin"></i> Loading evaluation...
                            </div>
                        </div>

                        <!-- Footer Legend -->
                        <div class="footer-section">
                            <div class="footer-box">
                                <div class="footer-title">Description</div>
                                <div class="footer-row">Outstanding</div>
                                <div class="footer-row">Very Satisfactory</div>
                                <div class="footer-row">Satisfactory</div>
                                <div class="footer-row">Fairly Satisfactory</div>
                                <div class="footer-row">Did Not Meet Expectations</div>
                            </div>

                            <div class="footer-box" style="margin-left: 40px;">
                                <div class="footer-title">Grading Scale</div>
                                <div class="footer-row">90-100</div>
                                <div class="footer-row">85-89</div>
                                <div class="footer-row">80-84</div>
                                <div class="footer-row">75-79</div>
                                <div class="footer-row">Below 75</div>
                            </div>

                            <div class="footer-box" style="margin-left: 40px;">
                                <div class="footer-title">Remarks</div>
                                <div class="footer-row">Passed</div>
                                <div class="footer-row">Passed</div>
                                <div class="footer-row">Passed</div>
                                <div class="footer-row">Passed</div>
                                <div class="footer-row">Failed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

</div>
@endsection

@section('scripts')
<script>
const API_ROUTES = {
    evaluationData: "{{ route('admin.grades.evaluation.data', $student->student_number) }}"
};

const STUDENT_NUMBER = "{{ $student->student_number }}";
</script>
<script src="{{ asset('js/grade_management/view_evaluation.js') }}"></script>
@endsection