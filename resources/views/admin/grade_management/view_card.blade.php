@extends('layouts.main')

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
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 5px;
        }

        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
            letter-spacing: 1px;
        }

        .school-info {
            font-size: 11px;
            margin: 2px 0;
            line-height: 1.3;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            letter-spacing: 2px;
        }

        .school-year {
            font-size: 13px;
            margin-top: 5px;
        }

        .student-info {
            margin: 15px 0;
            border: 2px solid #000;
            padding: 10px;
        }

        .info-row {
            display: flex;
            padding: 4px 0;
            font-size: 13px;
        }

        .info-label {
            font-weight: bold;
            width: 180px;
            flex-shrink: 0;
        }

        .info-value {
            flex: 1;
            border-bottom: 1px solid #000;
            padding: 0 5px;
        }

        .grades-section {
            margin: 20px 0;
        }

        .section-header {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
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
            font-size: 11px;
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
            margin-top: 20px;
            text-align: center;
        }

        .footer-box {
            padding: 10px;
            display: inline-block;
            max-width: 500px;
        }

        .footer-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 2px 0;
        }

        .footer-row .label {
            flex: 1;
        }

        .footer-row .value {
            font-weight: bold;
            text-align: right;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom no-print">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.grades.cards') }}">Grade Cards</a></li>
        <li class="breadcrumb-item active">View Report Card</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid no-print mb-3">
    <a href="{{ route('admin.grades.cards') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <button class="btn btn-danger" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="report-card-page">
    <img src="{{ asset('img/logo/trinity_logo.png') }}" class="watermark-logo" alt="Watermark">
    
    <div class="content">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('img/logo/trinity_logo.png') }}" class="logo" alt="School Logo">
            <div class="school-name">TRINITY POLYTECHNIC COLLEGE INC.</div>
            <div class="school-info">Golden Building, 2491 R. Lagman st. Lugam, M. Alas Highway, Brgy. Sta. Rosa, Cabanatuan City</div>
            <div class="school-info">Telephone Number: 0919 8011 60</div>
            <div class="school-info">Email: trinitypci@gmail.com</div>
            <div class="report-title">COPY OF GRADES</div>
            <div class="school-year">School Year {{ $semester->school_year_code ?? 'N/A' }}</div>
            <div class="school-year">{{ $semester->name }}</div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="info-row">
                <div class="info-label">NAME:</div>
                <div class="info-value">{{ strtoupper($student->last_name) }}, {{ strtoupper($student->first_name) }} {{ strtoupper($student->middle_name) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">TRACK AND STRAND:</div>
                <div class="info-value">{{ $student->strand_code ?? 'N/A' }} - {{ $student->level_name ?? 'N/A' }}</div>
                <div style="width: 20px;"></div>
                <div class="info-label" style="width: 150px;">GRADE & SECTION:</div>
                <div class="info-value" style="width: 200px;">{{ $student->section_code ?? 'IRREGULAR' }} {{ $student->student_type === 'regular' ? '- ' . $student->level_name : '' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">ADVISER:</div>
                <div class="info-value">{{ $adviser_name ?? 'N/A' }}</div>
            </div>
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
                        $core_subjects = [];
                        $applied_subjects = [];
                        $specialized_subjects = [];
                        
                        foreach($enrolled_subjects as $subject) {
                            $class_name = strtoupper($subject->class_name);
                            
                            if (
                                strpos($class_name, 'ORAL COMMUNICATION') !== false ||
                                strpos($class_name, 'KOMUNIKASYON') !== false ||
                                strpos($class_name, 'EARTH') !== false ||
                                strpos($class_name, 'GENERAL MATH') !== false ||
                                strpos($class_name, 'INTRODUCTION TO') !== false ||
                                strpos($class_name, 'PERSONAL DEVELOPMENT') !== false ||
                                strpos($class_name, 'PHYSICAL EDUCATION') !== false ||
                                strpos($class_name, 'P.E') !== false
                            ) {
                                $core_subjects[] = $subject;
                            } elseif (
                                strpos($class_name, 'APPLIED') !== false
                            ) {
                                $applied_subjects[] = $subject;
                            } else {
                                $specialized_subjects[] = $subject;
                            }
                        }
                        
                        $total_final = 0;
                        $count_with_grades = 0;
                    @endphp
                    
                    @if(count($core_subjects) > 0)
                        <tr>
                            <td colspan="5" class="category-header">CORE SUBJECTS</td>
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
                    
                    @if(count($applied_subjects) > 0)
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
                    
                    @if(count($specialized_subjects) > 0)
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
                    
                    @if(count($enrolled_subjects) == 0)
                        <tr>
                            <td colspan="5" class="text-center">No subjects enrolled for this semester</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <div class="footer-box">
                <div class="footer-title">Description</div>
                <div class="footer-row">
                    <span class="label">Outstanding</span>
                    <span class="value">90-100</span>
                </div>
                <div class="footer-row">
                    <span class="label">Very Satisfactory</span>
                    <span class="value">85-89</span>
                </div>
                <div class="footer-row">
                    <span class="label">Satisfactory</span>
                    <span class="value">80-84</span>
                </div>
                <div class="footer-row">
                    <span class="label">Fairly Satisfactory</span>
                    <span class="value">75-79</span>
                </div>
                <div class="footer-row">
                    <span class="label">Did Not Meet Expectations</span>
                    <span class="value">Below 75</span>
                </div>
            </div>
            
            <div class="footer-box">
                <div class="footer-title">Grading Scale</div>
                <div class="footer-row">
                    <span class="label">Passed</span>
                </div>
                <div class="footer-row">
                    <span class="label">Failed</span>
                </div>
                <div style="margin-top: 10px;">
                    <div class="footer-title">Remarks</div>
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
</div>

<div class="container-fluid no-print text-center mb-4">
    <a href="{{ route('admin.grades.cards') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <button class="btn btn-danger" onclick="window.print()">
        <i class="fas fa-print"></i> Print
    </button>
</div>
@endsection

@section('scripts')
@endsection