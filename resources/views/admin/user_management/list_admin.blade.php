@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .nav-tabs .nav-link { color: #6c757d; font-weight: 500; border: none; border-bottom: 2px solid transparent; padding: 0.75rem 1.5rem; }
        .nav-tabs .nav-link:hover { background-color: #f8f9fa; }
        .nav-tabs .nav-link.active { color: #007bff; background-color: transparent; border-bottom: 2px solid #007bff; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .tab-content { padding-top: 1rem; }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Admin List</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users-cog mr-2"></i>Admin Accounts
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.create_admin') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Add New Admin
                    </a>
                </div>
            </div>

            <div class="card-body">

                <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#activeAdmins" role="tab">
                            <i class="fas fa-check-circle mr-1"></i>Active
                            <span class="badge badge-secondary ml-1">{{ $admins->where('status', 1)->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#inactiveAdmins" role="tab">
                            <i class="fas fa-pause-circle mr-1"></i>Inactive
                            <span class="badge badge-secondary ml-1">{{ $admins->where('status', 0)->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="adminTabContent">

                    {{-- Active Admins --}}
                    <div class="tab-pane fade show active" id="activeAdmins" role="tabpanel">
                        <table id="activeAdminTable" class="table table-hover mt-2">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Created At</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins->where('status', 1) as $admin)
                                <tr>
                                    <td>{{ $admin->id }}</td>
                                    <td><strong>{{ $admin->admin_name }}</strong></td>
                                    <td>{{ $admin->email }}</td>
                                    <td>
                                        @if($admin->admin_type == 1)
                                            <span class="badge badge-primary">{{ $admin->admin_type_name }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $admin->admin_type_name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ date('M d, Y', strtotime($admin->created_at)) }}</td>
                                    <td class="text-center">
                                        @if($admin->can_edit)
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-secondary btn-edit"
                                                    data-id="{{ $admin->id }}"
                                                    data-name="{{ $admin->admin_name }}"
                                                    data-email="{{ $admin->email }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-reset-password"
                                                    data-id="{{ $admin->id }}"
                                                    data-name="{{ $admin->admin_name }}"
                                                    title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-deactivate"
                                                    data-id="{{ $admin->id }}"
                                                    data-name="{{ $admin->admin_name }}"
                                                    title="Deactivate">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">Protected</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Inactive Admins --}}
                    <div class="tab-pane fade" id="inactiveAdmins" role="tabpanel">
                        <table id="inactiveAdminTable" class="table table-hover mt-2">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Created At</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins->where('status', 0) as $admin)
                                <tr>
                                    <td>{{ $admin->id }}</td>
                                    <td><strong>{{ $admin->admin_name }}</strong></td>
                                    <td>{{ $admin->email }}</td>
                                    <td>
                                        @if($admin->admin_type == 1)
                                            <span class="badge badge-primary">{{ $admin->admin_type_name }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $admin->admin_type_name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ date('M d, Y', strtotime($admin->created_at)) }}</td>
                                    <td class="text-center">
                                        @if($admin->can_edit)
                                            <button type="button" class="btn btn-sm btn-secondary btn-activate"
                                                data-id="{{ $admin->id }}"
                                                data-name="{{ $admin->admin_name }}"
                                                title="Activate">
                                                <i class="fas fa-check mr-1"></i>Activate
                                            </button>
                                        @else
                                            <span class="badge badge-secondary">Protected</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit mr-2"></i>Edit Admin
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="editAdminForm">
                    <div class="modal-body">
                        <input type="hidden" id="editAdminId">

                        <div class="form-group">
                            <label>Admin Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control" id="editAdminName" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" class="form-control" id="editEmail" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        const API_ROUTES = {
            updateAdmin:    "{{ url('admin/update_admin') }}",
            resetPassword:  "{{ url('admin/reset_admin_password') }}",
            toggleStatus:   "{{ url('admin/toggle_admin_status') }}"
        };
        const CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection