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
                <i class="fas fa-user-graduate"></i> Enroll by Section
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Enroll Section</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 id="pageTitle">Section & Class Management</h1>
                </div>
                <div class="col-sm-6">
                    <a href="list_schoolyear.html" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back to School Years
                    </a>
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

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="sections-tab" data-toggle="tab" href="#sections" role="tab">
                        <i class="fas fa-users"></i> Regular Sections
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="irregular-tab" data-toggle="tab" href="#irregular" role="tab">
                        <i class="fas fa-user-clock"></i> Irregular Students
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="mainTabsContent">
                <!-- Regular Sections Tab -->
                <div class="tab-pane fade show active" id="sections" role="tabpanel">
                    <div class="card card-primary card-outline mt-3">
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
                                            <option value="11">Grade 11</option>
                                            <option value="12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Strand</label>
                                        <select class="form-control" id="sectionStrandFilter">
                                            <option value="">All Strands</option>
                                            <option value="STEM">STEM</option>
                                            <option value="ABM">ABM</option>
                                            <option value="HUMSS">HUMSS</option>
                                            <option value="GAS">GAS</option>
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

                    <div id="sectionsContainer" class="row"></div>
                </div>

                <!-- Irregular Students Tab -->
                <div class="tab-pane fade" id="irregular" role="tabpanel">
                    <div class="card card-warning card-outline mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter"></i> Student Filters</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Search Student</label>
                                        <input type="text" class="form-control" id="irregularSearchInput"
                                            placeholder="Name or Student ID...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Grade Level</label>
                                        <select class="form-control" id="irregularGradeFilter">
                                            <option value="">All</option>
                                            <option value="11">Grade 11</option>
                                            <option value="12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Strand</label>
                                        <select class="form-control" id="irregularStrandFilter">
                                            <option value="">All</option>
                                            <option value="STEM">STEM</option>
                                            <option value="ABM">ABM</option>
                                            <option value="HUMSS">HUMSS</option>
                                            <option value="GAS">GAS</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="irregularStudentsContainer"></div>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics" role="tabpanel">
                    <div class="row mt-3">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalSections">0</h3>
                                    <p>Total Sections</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="totalRegularStudents">0</h3>
                                    <p>Regular Students</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="totalIrregularStudents">0</h3>
                                    <p>Irregular Students</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="totalClasses">0</h3>
                                    <p>Total Classes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Students by Strand</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Strand</th>
                                                <th>Regular</th>
                                                <th>Irregular</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="strandStatsBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Class Enrollment Summary</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Class Name</th>
                                                <th>Enrolled</th>
                                            </tr>
                                        </thead>
                                        <tbody id="classStatsBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection