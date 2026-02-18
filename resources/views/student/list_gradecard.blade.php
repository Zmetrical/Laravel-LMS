@extends('layouts.main-student')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">My Grade Cards</li>
    </ol>
</nav>
@endsection

@section('styles')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #007bff;
    }

    .nav-tabs .nav-link.active {
        color: #007bff;
        background-color: transparent;
        border-color: transparent;
        border-bottom-color: #007bff;
        font-weight: 500;
    }

    .report-card-container {
        background: white;
        max-width: 8.5in;
        margin: 20px auto;
        padding: 0.5in;
        box-shadow: 0 0 20px rgba(0,0,0,0.15);
        position: relative;
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

    .grades-section {
        margin: 20px 0;
    }

    .section-header {
        text-align: center;
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 10px;
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

    .general-average-row {
        background: #e9ecef !important;
        font-weight: bold;
    }

    .general-average-row td {
        padding: 8px !important;
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

    /* Evaluation Summary Styles */
    .semester-section {
        margin: 25px 0;
        page-break-inside: avoid;
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

        .footer-box {
            margin: 5px 0 !important;
        }
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .report-card-container {
            margin: 0;
            padding: 0.5in;
            box-shadow: none !important;
        }

        .nav-tabs {
            display: none !important;
        }

        .semester-section {
            page-break-inside: avoid;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Tab Navigation -->
    <div class="col-12 no-print">
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="gradecard-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="report-card-tab" data-toggle="tab" href="#report-card-pane" role="tab">
                            <i class="fas fa-file-alt mr-2"></i>Report Card
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary-pane" role="tab">
                            <i class="fas fa-graduation-cap mr-2"></i>Evaluation Summary
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="col-12">
        <div class="tab-content" id="gradecard-tab-content">
            
            <!-- Report Card Tab -->
            <div class="tab-pane fade show active" id="report-card-pane" role="tabpanel">
                <div class="card no-print">
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="semester-selector">Select Semester</label>
                                    <select class="form-control" id="semester-selector">
                                        <option value="">Loading semesters...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Digital Report Card -->
                <div id="report-card-wrapper" style="display: none;">
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

                            <div class="report-title">REPORT CARD</div>
                            <div class="school-year">School Year <span id="school-year-display"></span></div>
                            <div class="school-year" id="semester-display"></div>

                            <!-- Student Information -->
                            <div class="student-info">
                                <table class="student-info-table">
                                    <tr>
                                        <td class="info-label">NAME:</td>
                                        <td class="info-value">{{ strtoupper(auth()->user()->last_name) }}, {{ strtoupper(auth()->user()->first_name) }} {{ strtoupper(auth()->user()->middle_name) }}</td>
                                        <td class="info-label">GRADE & SECTION:</td>
                                        <td class="info-value" id="level-section-display">-</td>
                                    </tr>
                                    <tr>
                                        <td class="info-label">TRACK AND STRAND:</td>
                                        <td class="info-value" id="strand-display">-</td>
                                        <td class="info-label">ADVISER:</td>
                                        <td class="info-value" id="adviser-display">N/A</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Grades Section -->
                            <div class="grades-section">
                                <div class="section-header">LEARNING PROGRESS AND ACHIEVEMENT</div>
                                
                                <table class="grades-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50%;">SUBJECTS</th>
                                            <th style="width: 12.5%;">FIRST<br>QUARTER</th>
                                            <th style="width: 12.5%;">SECOND<br>QUARTER</th>
                                            <th style="width: 12.5%;">SEMESTER<br>AVERAGE</th>
                                            <th style="width: 12.5%;">REMARKS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="grades-tbody">
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-info-circle text-muted"></i> Select a semester to view grades
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer -->
                            <div class="footer-section">
                                <div class="footer-box">
                                    <div class="footer-title">Description</div>
                                    <div class="footer-row"><span class="label">Outstanding</span></div>
                                    <div class="footer-row"><span class="label">Very Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Fairly Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Did Not Meet Expectations</span></div>
                                </div>

                                <div class="footer-box" style="margin-left: 40px;">
                                    <div class="footer-title">Grading Scale</div>
                                    <div class="footer-row"><span class="label">90-100</span></div>
                                    <div class="footer-row"><span class="label">85-89</span></div>
                                    <div class="footer-row"><span class="label">80-84</span></div>
                                    <div class="footer-row"><span class="label">75-79</span></div>
                                    <div class="footer-row"><span class="label">Below 75</span></div>
                                </div>

                                <div class="footer-box" style="margin-left: 40px;">
                                    <div class="footer-title">Remarks</div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Failed</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mb-4 no-print">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report Card
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary/Evaluation Tab -->
            <div class="tab-pane fade" id="summary-pane" role="tabpanel">
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
                                        <td class="info-value" colspan="3">{{ strtoupper(auth()->user()->last_name) }}, {{ strtoupper(auth()->user()->first_name) }} {{ strtoupper(auth()->user()->middle_name) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="info-label">STUDENT NUMBER:</td>
                                        <td class="info-value">{{ auth()->user()->student_number }}</td>
                                        <td class="info-label">STUDENT TYPE:</td>
                                        <td class="info-value">{{ strtoupper(auth()->user()->student_type) }}</td>
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
                                    <div class="footer-row"><span class="label">Outstanding</span></div>
                                    <div class="footer-row"><span class="label">Very Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Fairly Satisfactory</span></div>
                                    <div class="footer-row"><span class="label">Did Not Meet Expectations</span></div>
                                </div>

                                <div class="footer-box" style="margin-left: 40px;">
                                    <div class="footer-title">Grading Scale</div>
                                    <div class="footer-row"><span class="label">90-100</span></div>
                                    <div class="footer-row"><span class="label">85-89</span></div>
                                    <div class="footer-row"><span class="label">80-84</span></div>
                                    <div class="footer-row"><span class="label">75-79</span></div>
                                    <div class="footer-row"><span class="label">Below 75</span></div>
                                </div>

                                <div class="footer-box" style="margin-left: 40px;">
                                    <div class="footer-title">Remarks</div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Passed</span></div>
                                    <div class="footer-row"><span class="label">Failed</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mb-4 no-print">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Evaluation
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/student/list_gradecard.js') }}"></script>
@endsection