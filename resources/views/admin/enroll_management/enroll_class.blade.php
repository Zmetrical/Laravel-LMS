@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>


    </style>
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Enroll by Class
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Enroll Class</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar - Class List -->
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book"></i> Classes</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <input type="text" class="form-control" id="classSearch" placeholder="Search classes...">
                        </div>
                        <div class="list-group list-group-flush" id="classListGroup"
                            style="max-height: 600px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            <!-- Main Content - Students -->
            <div class="col-md-9">
                <div id="noClassSelected" class="alert alert-info">
                    <h5><i class="icon fas fa-info"></i> No Class Selected</h5>
                    Please select a class from the left sidebar to view and manage student enrollments.
                </div>

                <div id="enrollmentSection" style="display:none;">
                    <!-- Class Info Banner -->
                    <div class="callout callout-primary">
                        <h5 id="selectedClassName"></h5>
                        <p class="mb-0">Manage student enrollments for this class</p>
                    </div>

                    <!-- Filters -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter"></i> Student Filters</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Search</label>
                                        <input type="text" class="form-control" id="studentSearch"
                                            placeholder="Name or ID...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Grade Level</label>
                                        <select class="form-control" id="gradeFilter">
                                            <option value="">All</option>
                                            <option value="11">Grade 11</option>
                                            <option value="12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Strand</label>
                                        <select class="form-control" id="strandFilter">
                                            <option value="">All</option>
                                            <option value="STEM">STEM</option>
                                            <option value="ABM">ABM</option>
                                            <option value="HUMSS">HUMSS</option>
                                            <option value="GAS">GAS</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Section</label>
                                        <select class="form-control" id="sectionFilter">
                                            <option value="">All</option>
                                            <option value="A">Section A</option>
                                            <option value="B">Section B</option>
                                            <option value="C">Section C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Name Starts With</label>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <button type="button" class="btn btn-outline-secondary name-filter"
                                                data-filter="first">First</button>
                                            <button type="button" class="btn btn-outline-secondary name-filter"
                                                data-filter="last">Last</button>
                                            <input type="text" class="form-control form-control-sm" id="nameLetterFilter"
                                                placeholder="A-Z" maxlength="1" style="width: 50px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Students -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users"></i> Available Students</h3>
                            <div class="card-tools">
                                <span class="badge badge-primary" id="availableCount">0</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-3 bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllAvailable">
                                        <label class="custom-control-label" for="selectAllAvailable">
                                            <strong>Select All Available</strong>
                                        </label>
                                    </div>
                                    <button class="btn btn-primary btn-sm" id="enrollBtn">
                                        <i class="fas fa-arrow-down"></i> Enroll Selected (<span
                                            id="selectedCount">0</span>)
                                    </button>
                                </div>
                            </div>
                            <div style="max-height: 450px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 50px;"></th>
                                            <th>Name</th>
                                            <th>Student ID</th>
                                            <th>Grade</th>
                                            <th>Strand</th>
                                            <th>Section</th>
                                        </tr>
                                    </thead>
                                    <tbody id="availableStudentsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Enrolled Students -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check"></i> Enrolled Students</h3>
                            <div class="card-tools">
                                <span class="badge badge-info" id="enrolledCount">0</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-3 bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllEnrolled">
                                        <label class="custom-control-label" for="selectAllEnrolled">
                                            <strong>Select All Enrolled</strong>
                                        </label>
                                    </div>
                                    <button class="btn btn-primary btn-sm" id="unenrollBtn">
                                        <i class="fas fa-arrow-up"></i> Unenroll Selected (<span
                                            id="unenrollCount">0</span>)
                                    </button>
                                </div>
                            </div>
                            <div style="max-height: 450px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 50px;"></th>
                                            <th>Name</th>
                                            <th>Student ID</th>
                                            <th>Grade</th>
                                            <th>Strand</th>
                                            <th>Section</th>
                                        </tr>
                                    </thead>
                                    <tbody id="enrolledStudentsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection