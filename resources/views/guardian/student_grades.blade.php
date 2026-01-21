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
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Subject</th>
                                <th class="text-center" style="min-width: 100px;">Quarter 1</th>
                                <th class="text-center" style="min-width: 100px;">Quarter 2</th>
                                <th class="text-center" style="min-width: 100px;">Final Grade</th>
                                <th class="text-center" style="min-width: 100px;">Remarks</th>
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
        $('#grades-tbody').html('<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading grades...</td></tr>');
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
                    $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-muted py-4">No grades recorded for this semester yet.</td></tr>');
                }
            },
            error: function() {
                $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-danger py-4">Error loading grades. Please try again.</td></tr>');
            }
        });
    }

    function displayGrades(grades) {
        let html = '';
        
        grades.forEach(function(grade) {
            html += '<tr>';
            html += '<td class="font-weight-bold">' + grade.class_name + '</td>';
            
            // Quarter 1
            html += '<td class="text-center">';
            html += grade.q1_transmuted_grade !== null 
                ? parseFloat(grade.q1_transmuted_grade).toFixed(2) 
                : '-';
            html += '</td>';
            
            // Quarter 2
            html += '<td class="text-center">';
            html += grade.q2_transmuted_grade !== null 
                ? parseFloat(grade.q2_transmuted_grade).toFixed(2) 
                : '-';
            html += '</td>';
            
            // Final Grade
            html += '<td class="text-center font-weight-bold">';
            html += grade.final_grade !== null 
                ? parseFloat(grade.final_grade).toFixed(2) 
                : '-';
            html += '</td>';
            
            // Remarks (no badge, just plain text)
            html += '<td class="text-center">';
            html += grade.remarks || '-';
            html += '</td>';
            
            html += '</tr>';
        });
        
        $('#grades-tbody').html(html);
    }
});
</script>
@endsection