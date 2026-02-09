@extends('layouts.main-teacher')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .report-card-page { 
                margin: 0;
                padding: 0;
                box-shadow: none !important;
            }
        }

        .report-card-page {
            background: white;
            max-width: 8.5in;
            min-height: 11in;
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

        .student-info {
            margin: 15px 0;
            border-collapse: collapse;
        }

        .student-info-table {
            width: 100%;
            border-collapse: collapse;
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

        .info-divider {
            width: 15px;
            flex-shrink: 0;
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

        .text-center {
            text-align: center;
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

        .footer-row .label {
            display: inline-block;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom no-print">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('teacher.grades.cards') }}">Grade Cards</a></li>
        <li class="breadcrumb-item active">View Report Card</li>
    </ol>
@endsection

@section('content')
<div class="report-card-page">
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
        <div class="report-title">Report Card</div>
        <div class="school-year">School Year {{ $semester->school_year_code ?? 'N/A' }}</div>
        <div class="school-year">{{ $semester->name }}</div>

        <!-- Student Information -->
        <div class="student-info">
            <table class="student-info-table">
                <tr>
                    <td class="info-label">NAME:</td>
                    <td class="info-value">{{ strtoupper($student->last_name) }}, {{ strtoupper($student->first_name) }} {{ strtoupper($student->middle_name) }}</td>
                    <td class="info-label">GRADE & SECTION:</td>
                    <td class="info-value">{{ $student->section_code ?? 'IRREGULAR' }}</td>
                </tr>
                <tr>
                    <td class="info-label">TRACK AND STRAND:</td>
                    <td class="info-value">{{ $student->strand_code ?? '' }} - {{ $student->level_name ?? '' }}</td>
                    <td class="info-label">ADVISER:</td>
                    <td class="info-value">{{ $adviser_name ?? '' }}</td>
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
                <tbody>
                    @php
                        // Group subjects by category from database
                        $core_subjects = $enrolled_subjects->where('class_category', 'CORE SUBJECT');
                        $applied_subjects = $enrolled_subjects->where('class_category', 'APPLIED SUBJECT');
                        $specialized_subjects = $enrolled_subjects->where('class_category', 'SPECIALIZED SUBJECT');
                        
                        $total_final = 0;
                        $count_with_grades = 0;
                    @endphp
                    
                    @if($core_subjects->count() > 0)
                        <tr>
                            <td colspan="5" class="category-header">CORE SUBJECT</td>
                        </tr>
                        @foreach($core_subjects as $subject)
                            @if($subject->final_grade)
                                @php
                                    $total_final += $subject->final_grade;
                                    $count_with_grades++;
                                @endphp
                            @endif
                            <tr>
                                <td>{{ $subject->class_name }}</td>
                                <td class="text-center">{{ $subject->q1_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->q2_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->final_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->remarks ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                    
                    @if($applied_subjects->count() > 0)
                        <tr>
                            <td colspan="5" class="category-header">APPLIED SUBJECT</td>
                        </tr>
                        @foreach($applied_subjects as $subject)
                            @if($subject->final_grade)
                                @php
                                    $total_final += $subject->final_grade;
                                    $count_with_grades++;
                                @endphp
                            @endif
                            <tr>
                                <td>{{ $subject->class_name }}</td>
                                <td class="text-center">{{ $subject->q1_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->q2_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->final_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->remarks ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                    
                    @if($specialized_subjects->count() > 0)
                        <tr>
                            <td colspan="5" class="category-header">SPECIALIZED SUBJECT</td>
                        </tr>
                        @foreach($specialized_subjects as $subject)
                            @if($subject->final_grade)
                                @php
                                    $total_final += $subject->final_grade;
                                    $count_with_grades++;
                                @endphp
                            @endif
                            <tr>
                                <td>{{ $subject->class_name }}</td>
                                <td class="text-center">{{ $subject->q1_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->q2_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->final_grade ?? '' }}</td>
                                <td class="text-center">{{ $subject->remarks ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                    
                    @if($count_with_grades > 0)
                        <tr class="general-average-row">
                            <td colspan="3" style="text-align: right; padding-right: 20px;">GENERAL AVERAGE</td>
                            <td colspan="2" class="text-center">{{ number_format($total_final / $count_with_grades, 2) }}</td>
                        </tr>
                    @endif
                    
                    @if($enrolled_subjects->count() == 0)
                        <tr>
                            <td colspan="5" class="text-center">No subjects enrolled for this semester</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="footer-section">
            <div class="footer-box">
                <div class="footer-title">Description</div>
                <div class="footer-row">
                    <span class="label">Outstanding</span>
                </div>
                <div class="footer-row">
                    <span class="label">Very Satisfactory</span>
                </div>
                <div class="footer-row">
                    <span class="label">Satisfactory</span>
                </div>
                <div class="footer-row">
                    <span class="label">Fairly Satisfactory</span>
                </div>
                <div class="footer-row">
                    <span class="label">Did Not Meet Expectations</span>
                </div>
            </div>

            <div class="footer-box" style="margin-left: 40px;">
                <div class="footer-title">Grading Scale</div>
                <div class="footer-row">
                    <span class="label">90-100</span>
                </div>
                <div class="footer-row">
                    <span class="label">85-89</span>
                </div>
                <div class="footer-row">
                    <span class="label">80-84</span>
                </div>
                <div class="footer-row">
                    <span class="label">75-79</span>
                </div>
                <div class="footer-row">
                    <span class="label">Below 75</span>
                </div>
            </div>

            <div class="footer-box" style="margin-left: 40px;">
                <div class="footer-title">Remarks</div>
                <div class="footer-row">
                    <span class="label">Passed</span>
                </div>
                <div class="footer-row">
                    <span class="label">Passed</span>
                </div>
                <div class="footer-row">
                    <span class="label">Passed</span>
                </div>
                <div class="footer-row">
                    <span class="label">Passed</span>
                </div>
                <div class="footer-row">
                    <span class="label">Failed</span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="container-fluid no-print text-center mb-4">
    <a href="{{ route('teacher.grades.cards') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
    <button class="btn btn-primary" onclick="exportToPDF()">
        <i class="fas fa-file-pdf"></i> Export
    </button>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
function exportToPDF() {
    const exportBtn = event.target.closest('button');
    const originalHTML = exportBtn.innerHTML;
    exportBtn.disabled = true;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';

    html2canvas(document.querySelector('.report-card-page'), {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jspdf.jsPDF({
            orientation: 'portrait',
            unit: 'in',
            format: 'letter'
        });
        const imgWidth = 8.5;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        
        const studentName = document.querySelector('.info-value')?.textContent.trim() || 'Student';
        const schoolYear = document.querySelectorAll('.school-year')[0]?.textContent.trim() || '';
        const filename = `Report_Card_${studentName.replace(/[^a-z0-9]/gi, '_')}_${schoolYear.replace(/[^a-z0-9]/gi, '_')}.pdf`;
        
        pdf.save(filename);
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalHTML;
    }).catch(error => {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF. Please try again.');
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalHTML;
    });
}
</script>
@endsection