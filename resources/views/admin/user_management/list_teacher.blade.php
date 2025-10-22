@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif


    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">


    <style>


    </style>
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Teacher List
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Teacher List</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')

          <div class="container-fluid">
            <div class="card">
              <div class="card-body">
                <div class="mb-3">
                  <strong><span id="countNumber">50</span> participants found</strong>
                </div>

                <div class="row">
                  <div class="col-auto">
                    <strong class="d-block mb-1" style="font-size: 14px;">First name</strong>
                    <div id="firstNameFilter" class="btn-group btn-group-sm" role="group"></div>
                  </div>
                </div>

                <div class="row mt-2">
                  <div class="col-auto">
                    <strong class="d-block mb-1" style="font-size: 14px;">Last name</strong>
                    <div id="lastNameFilter" class="btn-group btn-group-sm" role="group"></div>
                  </div>
                </div>

                <div class="mt-2">
                  <button class="btn btn-sm btn-secondary" id="clearFilters">
                    <i class="fas fa-undo"></i> Clear filters
                  </button>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped table-hover mb-0" id="teacherTable" style="width: 100%;">
                    <thead style="position: sticky; top: 0; background-color: #fff; z-index: 10; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);">
                      <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="teacherTableBody">
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

@endsection

@section('scripts')

    <!-- DataTables -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- ExcelJs (CDN stays as-is) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>


    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection