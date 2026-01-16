@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
        .component-tab-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .component-tab-group .btn {
            flex: 1;
            min-width: 100px;
        }
        @media (max-width: 768px) {
            .component-tab-group .btn {
                flex: 1 1 calc(50% - 0.5rem);
                min-width: auto;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('student.list_grade') }}">My Grades</a></li>
        <li class="breadcrumb-item active">{{ $class->class_name }} - {{ $quarter->name }}</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Class Header -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1">
                        <i class="fas fa-book"></i> 
                        <strong>{{ $class->class_name }}</strong>
                    </h4>
                    <small class="text-muted">{{ $quarter->name }}</small>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('student.list_grade') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Filter Card -->
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-3">
            <label class="mb-2"><strong><i class="fas fa-filter"></i> View Components:</strong></label>
            <div class="component-tab-group">
                <button type="button" class="btn btn-primary component-filter-btn active" data-component="all">
                    <i class="fas fa-th-list"></i> Overall Grade
                </button>
                <button type="button" class="btn btn-outline-primary component-filter-btn" data-component="WW">
                    <i class="fas fa-pen"></i> Written Works
                </button>
                <button type="button" class="btn btn-outline-primary component-filter-btn" data-component="PT">
                    <i class="fas fa-tasks"></i> Performance Tasks
                </button>
                <button type="button" class="btn btn-outline-primary component-filter-btn" data-component="QA">
                    <i class="fas fa-clipboard-check"></i> Assessment
                </button>
            </div>
        </div>
    </div>

    <!-- Grades Content -->
    <div id="gradesContent">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading grade details...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getDetails: "{{ route('student.grades.details.data', ['classId' => $classId, 'quarterId' => $quarterId]) }}"
        };
        
        const CLASS_INFO = {
            ww_perc: {{ $class->ww_perc }},
            pt_perc: {{ $class->pt_perc }},
            qa_perc: {{ $class->qa_perce }}
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection