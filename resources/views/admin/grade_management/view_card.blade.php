@extends('layouts.main')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .report-card-container {
            background: white;
            border: 3px solid #007bff;
            border-radius: 8px;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .report-card-header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .report-card-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin: 5px 0;
        }
        .school-address {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .report-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 15px;
            color: #212529;
        }
        .report-subtitle {
            font-size: 1rem;
            color: #495057;
            margin-top: 5px;
        }
        .student-info-section {
            margin-bottom: 25px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            width: 180px;
            flex-shrink: 0;
        }
        .info-value {
            color: #212529;
            flex-grow: 1;
        }
        .grades-section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #007bff;
        }
        .grades-table {
            width: 100%;
            margin-bottom: 0;
        }
        .grades-table th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-weight: 600;
            text-align: center;
            font-size: 0.9rem;
        }
        .grades-table td {
            padding: 8px 10px;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
        }
        .grades-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-center-td {
            text-align: center;
        }
        .badge-passed { 
            background-color: #28a745; 
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .badge-failed { 
            background-color: #dc3545; 
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .badge-inc { 
            background-color: #ffc107; 
            color: #212529;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .badge-drp, .badge-w { 
            background-color: #6c757d; 
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .summary-section {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #007bff;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-label {
            font-weight: 600;
            color: #495057;
        }
        .summary-value {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1rem;
        }
        .grading-scale {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .grading-scale-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        .grading-scale-row {
            display: flex;
            padding: 3px 0;
        }
        .grading-scale-label {
            width: 200px;
            flex-shrink: 0;
        }
        .grading-scale-value {
            flex-grow: 1;
            text-align: right;
        }
        .action-buttons {
            margin-top: 30px;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .report-card-container {
                border: 2px solid #000;
                box-shadow: none;
            }
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
<br>
<div class="container-fluid no-print">
    <div class="mb-3">
        <a href="{{ route('admin.grades.cards') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Grade Cards
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Report Card
        </button>
    </div>
</div>

<div class="container-fluid">
    <div class="report-card-container">
        <!-- Header -->
        <div class="report-card-header">
            <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="School Logo" class="report-card-logo">
            <div class="school-name">TRINITY POLYTECHNIC COLLEGE INC.</div>
            <div class="school-address">Golden Building, 2491 R. Lagman st. Lugam, M. Alas Highway, Brgy. Sta. Rosa, Cabanatuan City</div>
            <div class="school-address">Telephone Number: 0919 8011 60</div>
            <div class="school-address">Email: trinitypci@gmail.com</div>
            <div class="report-title">COPY OF GRADES</div>
            <div class="report-subtitle">School Year {{ $semester->display_name }}</div>
        </div>

        <!-- Student Information -->
        <div class="student-info-section">
            <div class="info-row">
                <div class="info-label">NAME:</div>
                <div class="info-value">{{ strtoupper($student->last_name) }}, {{ strtoupper($student->first_name) }} {{ strtoupper($student->middle_name) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">STUDENT NUMBER:</div>
                <div class="info-value">{{ $student->student_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">TRACK AND STRAND:</div>
                <div class="info-value">
                    @if($student->strand_code && $student->level_name)
                        {{ $student->strand_code }} - {{ $student->level_name }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">GRADE & SECTION:</div>
                <div class="info-value">{{ $student->section_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">STUDENT TYPE:</div>
                <div class="info-value">
                    <span class="badge badge-{{ $student->student_type === 'regular' ? 'primary' : 'secondary' }}">
                        {{ strtoupper($student->student_type) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Grades Section -->
        <div class="grades-section">
            <div class="section-title">LEARNING PROGRESS AND ACHIEVEMENT</div>
            
            <table class="table table-bordered grades-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">SUBJECTS</th>
                        <th style="width: 12%;">FIRST<br>QUARTER</th>
                        <th style="width: 12%;">SECOND<br>QUARTER</th>
                        <th style="width: 13%;">SEMESTER<br>AVERAGE</th>
                        <th style="width: 13%;">REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                        <tr>
                            <td>
                                <strong>{{ $grade->class_code }}</strong><br>
                                <small>{{ $grade->class_name }}</small>
                            </td>
                            <td class="text-center-td">{{ $grade->q1_grade ?? '-' }}</td>
                            <td class="text-center-td">{{ $grade->q2_grade ?? '-' }}</td>
                            <td class="text-center-td"><strong>{{ $grade->final_grade }}</strong></td>
                            <td class="text-center-td">
                                <span class="badge-{{ strtolower($grade->remarks) }}">{{ $grade->remarks }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No grades available</td>
                        </tr>
                    @endforelse
                    
                    @if(count($grades) > 0)
                        <tr style="background: #e9ecef;">
                            <td colspan="3" class="text-right"><strong>GENERAL AVERAGE</strong></td>
                            <td class="text-center-td" colspan="2"><strong style="font-size: 1.1rem; color: #007bff;">{{ number_format($statistics['general_average'], 2) }}</strong></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        @if(count($grades) > 0)
        <div class="summary-section">
            <div class="summary-row">
                <span class="summary-label">Total Subjects Enrolled:</span>
                <span class="summary-value">{{ $statistics['total_subjects'] }}</span>
            </div>
        </div>
        @endif

        <!-- Grading Scale -->
        <div class="grading-scale">
            <div class="grading-scale-title">Grading Scale</div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">Outstanding</span>
                <span class="grading-scale-value">90-100</span>
            </div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">Very Satisfactory</span>
                <span class="grading-scale-value">85-89</span>
            </div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">Satisfactory</span>
                <span class="grading-scale-value">80-84</span>
            </div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">Fairly Satisfactory</span>
                <span class="grading-scale-value">75-79</span>
            </div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">Did Not Meet Expectations</span>
                <span class="grading-scale-value">Below 75</span>
            </div>
        </div>

        <div class="grading-scale mt-3">
            <div class="grading-scale-title">Remarks</div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">PASSED</span>
                <span class="grading-scale-value">Student passed the subject</span>
            </div>
            <div class="grading-scale-row">
                <span class="grading-scale-label">FAILED</span>
                <span class="grading-scale-value">Student failed the subject</span>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid no-print">
    <div class="action-buttons mb-4">
        <a href="{{ route('admin.grades.cards') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Grade Cards
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Report Card
        </button>
    </div>
</div>
@endsection

@section('scripts')
@endsection