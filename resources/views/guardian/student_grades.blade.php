@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-graduation-cap breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('guardian.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">{{ $student->full_name }}</li>
    </ol>
</nav>
@endsection

@section('styles')
<style>
    .student-info-card {
        position: sticky;
        top: 70px;
        z-index: 100;
    }
    
    @media (max-width: 767.98px) {
        .student-info-card {
            position: relative;
            top: 0;
        }
        
        .profile-img-mobile {
            width: 80px;
            height: 80px;
        }
        
        .student-detail {
            font-size: 0.9rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .table th, .table td {
            padding: 0.5rem 0.25rem;
            white-space: nowrap;
        }
        
        .component-score {
            font-size: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Student Info Card -->
    <div class="col-12">
        <div class="card student-info-card">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img class="img-circle profile-img-mobile" 
                             src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                             alt="Student"
                             style="width: 60px; height: 60px; object-fit: cover;">
                    </div>
                    <div class="col">
                        <h5 class="mb-1 font-weight-bold">{{ $student->full_name }}</h5>
                        <div class="student-detail text-muted small">
                            <span class="mr-3"><i class="fas fa-id-card mr-1"></i>{{ $student->student_number }}</span>
                            <span class="mr-3"><i class="fas fa-layer-group mr-1"></i>{{ $student->level_name }}</span>
                            <span><i class="fas fa-users mr-1"></i>{{ $student->section_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-12">
        <!-- Semester Selector -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h3 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2"></i>Select Semester</h3>
            </div>
            <div class="card-body p-3">
                @if($semesters->count() > 0)
                <div class="form-group mb-0">
                    <select class="form-control" id="semester-selector">
                        <option value="">-- Select Semester --</option>
                        @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" {{ $semester->status === 'active' ? 'selected' : '' }}>
                            {{ $semester->display_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle mr-2"></i> No grade records found for this student yet.
                </div>
                @endif
            </div>
        </div>

        <!-- Grades Table -->
        <div class="card" id="grades-card" style="display: none;">
            <div class="card-header py-2">
                <h3 class="card-title mb-0"><i class="fas fa-chart-bar mr-2"></i>Grades Report</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="2" class="align-middle" style="min-width: 120px;">Subject</th>
                                <th colspan="3" class="text-center">Quarter 1</th>
                                <th colspan="3" class="text-center">Quarter 2</th>
                                <th rowspan="2" class="text-center align-middle">Final</th>
                                <th rowspan="2" class="text-center align-middle">Remarks</th>
                            </tr>
                            <tr>
                                <th class="text-center">WW</th>
                                <th class="text-center">PT</th>
                                <th class="text-center">QA</th>
                                <th class="text-center">WW</th>
                                <th class="text-center">PT</th>
                                <th class="text-center">QA</th>
                            </tr>
                        </thead>
                        <tbody id="grades-tbody">
                            <!-- Will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const studentNumber = '{{ $student->student_number }}';

    // Auto-load active semester on page load
    const activeSemester = $('#semester-selector').find('option:selected').val();
    if (activeSemester) {
        loadGrades(activeSemester);
    }

    $('#semester-selector').change(function() {
        const semesterId = $(this).val();
        
        if (!semesterId) {
            $('#grades-card').hide();
            return;
        }

        loadGrades(semesterId);
    });

    function loadGrades(semesterId) {
        // Show loading
        $('#grades-tbody').html('<tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading grades...</td></tr>');
        $('#grades-card').show();
        
        // Fetch grades
        $.ajax({
            url: '{{ route("guardian.student.grades.data", $student->student_number) }}',
            method: 'GET',
            data: { semester_id: semesterId },
            success: function(response) {
                if (response.grades && response.grades.length > 0) {
                    displayGrades(response.grades);
                } else {
                    $('#grades-tbody').html('<tr><td colspan="9" class="text-center text-muted py-4">No grades recorded for this semester yet.</td></tr>');
                }
            },
            error: function() {
                $('#grades-tbody').html('<tr><td colspan="9" class="text-center text-danger py-4">Error loading grades. Please try again.</td></tr>');
            }
        });
    }

    function displayGrades(grades) {
        let html = '';
        
        grades.forEach(function(grade) {
            html += '<tr>';
            html += '<td><strong>' + grade.class_name + '</strong></td>';
            
            // Q1 Components
            html += '<td class="text-center component-score">' + formatScore(grade.q1_ww_ws, grade.ww_perc) + '</td>';
            html += '<td class="text-center component-score">' + formatScore(grade.q1_pt_ws, grade.pt_perc) + '</td>';
            html += '<td class="text-center component-score">' + formatScore(grade.q1_qa_ws, grade.qa_perc) + '</td>';
            
            // Q2 Components
            html += '<td class="text-center component-score">' + formatScore(grade.q2_ww_ws, grade.ww_perc) + '</td>';
            html += '<td class="text-center component-score">' + formatScore(grade.q2_pt_ws, grade.pt_perc) + '</td>';
            html += '<td class="text-center component-score">' + formatScore(grade.q2_qa_ws, grade.qa_perc) + '</td>';
            
            // Final Grade
            html += '<td class="text-center"><strong>';
            if (grade.final_grade !== null) {
                html += parseFloat(grade.final_grade).toFixed(2);
            } else {
                html += '-';
            }
            html += '</strong></td>';
            
            // Remarks
            html += '<td class="text-center"><small>' + (grade.remarks || '-') + '</small></td>';
            
            html += '</tr>';
        });
        
        $('#grades-tbody').html(html);
    }

    function formatScore(score, weight) {
        if (score === null || score === undefined) {
            return '-';
        }
        return parseFloat(score).toFixed(2) + '<br><small class="text-muted">(' + weight + '%)</small>';
    }
});
</script>
@endsection