@extends('layouts.main')
@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection
@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-users"></i> Regular Sections
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Regular Sections</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">

                <div class="col-sm-6">
                    {{-- Future: Add Section Button --}}
                    {{-- <button class="btn btn-success float-right" id="addSectionBtn">
                        <i class="fas fa-plus"></i> Add Section
                    </button> --}}
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- School Year Info -->
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-calendar-alt"></i> School Year 2024-2025</h5>
                Viewing section and class enrollments for the current academic year.
            </div>

            <!-- Filters Card -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Grade Level</label>
                                <select class="form-control" id="sectionGradeFilter">
                                    <option value="">All Grades</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Strand</label>
                                <select class="form-control" id="sectionStrandFilter">
                                    <option value="">All Strands</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search Section</label>
                                <input type="text" class="form-control" id="sectionSearchInput"
                                    placeholder="Search...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-info btn-block" id="resetFiltersBtn">
                                    <i class="fas fa-redo"></i> Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center" style="display: none;">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-2">Loading sections...</p>
            </div>

            <!-- Sections Container -->
            <div id="sectionsContainer" class="row"></div>
        </div>
    </section>

    <!-- Section Details Modal -->
    <div class="modal fade" id="sectionDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-users"></i> Section Details
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="sectionDetailsBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getSections: "{{ route('admin.sections.data') }}",
            getDetails: "{{ route('admin.sections.details', ['id' => ':id']) }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection