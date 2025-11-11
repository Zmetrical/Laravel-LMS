@extends('layouts.main')

@section('styles')
  @if(isset($styles))
    @foreach($styles as $style)
      <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
    @endforeach
  @endif

  <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Teacher List</li>
    </ol>
@endsection

@section('content')
<br>
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <!-- Filter Area -->
        <div class="row align-items-end mb-3">
          <!-- Search Filter (Teacher Name) -->
          <div class="col-auto">
            <label class="mb-1">Teacher Name</label>
            <input type="text" class="form-control" id="teacherName" placeholder="Enter teacher name">
          </div>

          <!-- Email Filter -->
          <div class="col-auto">
            <label class="mb-1">Email</label>
            <input type="text" class="form-control" id="teacherEmail" placeholder="Enter email">
          </div>

          <!-- Gender Filter -->
          <div class="col-auto">
            <label class="mb-1">Gender</label>
            <select class="form-control" id="teacherGender">
              <option value="">All</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="col-auto">
            <label class="mb-1">Status</label>
            <select class="form-control" id="teacherStatus">
              <option value="">All</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>

          <div class="col-auto ml-auto">
            <button class="btn btn-secondary" id="clearFilters">
              <i class="fas fa-undo"></i> Clear filters
            </button>
          </div>
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
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="teacherTableBody">
              @foreach ($teachers as $teacher)
                <tr>
                  <td>{{ $teacher->first_name }}</td>
                  <td>{{ $teacher->last_name }}</td>
                  <td>{{ $teacher->email }}</td>
                  <td>{{ $teacher->phone }}</td>
                  <td>{{ $teacher->gender }}</td>
                  <td>
                    @if($teacher->status == 1)
                      <span class="badge badge-success">Active</span>
                    @else
                      <span class="badge badge-secondary">Inactive</span>
                    @endif
                  </td>
                  <td>
                    <a href="{{ route('profile.teacher.show', $teacher->id) }}" class="btn btn-sm btn-info">
                      <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="{{ route('profile.teacher.edit', $teacher->id) }}" class="btn btn-sm btn-primary">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                  </td>
                </tr>
              @endforeach
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

  <!-- ExcelJs -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>

  @if(isset($scripts))
    @foreach($scripts as $script)
      <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
  @endif
@endsection