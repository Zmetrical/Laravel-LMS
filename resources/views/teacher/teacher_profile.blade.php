@extends('layouts.main-teacher')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .expand-btn {
            cursor: pointer;
            transition: transform 0.2s;
            border: none;
            background: transparent;
            padding: 0.25rem 0.5rem;
            color: #6c757d;
        }
        .expand-btn:hover {
            color: #007bff;
        }
        .expand-btn.expanded {
            transform: rotate(90deg);
        }
        .expand-btn i {
            font-size: 1rem;
        }
        
        .classes-detail-row {
            background-color: #f8f9fa;
        }
        .classes-detail-cell {
            padding: 1rem !important;
            border-top: none !important;
        }
        .classes-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 0.75rem;
        }
        .class-item {
            padding: 0.75rem;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 0.25rem;
        }
        .class-header {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .class-code {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .sections-list {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #dee2e6;
        }
        .section-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.125rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .no-classes {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 2rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item active">My Profile</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column - Profile Picture -->
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img id="profileImagePreview" 
                                 class="profile-user-img img-fluid img-circle mb-4" 
                                 src="{{ $teacher->profile_image ? asset('storage/' . $teacher->profile_image) : asset('img/default-avatar.png') }}" 
                                 alt="Profile Image"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        </div>

                        @if($mode === 'edit')
                        <div class="text-center mb-3">
                            <label for="profileImageInput" class="btn btn-secondary mb-0">
                                <i class="fas fa-camera"></i> Upload Photo
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" class="d-none">
                        </div>
                        @endif

                        <h3 class="profile-username text-center">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </h3>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Email:</b> <span class="float-right">{{ $teacher->email }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Phone Number:</b> <span class="float-right">{{ $teacher->phone }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Gender:</b> <span class="float-right">{{ $teacher->gender ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Date Joined:</b> 
                                <span class="float-right">
                                    {{ $teacher->created_at ? date('M d, Y', strtotime($teacher->created_at)) : 'N/A' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column - Personal Information -->
            <div class="col-md-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Personal Information</h3>
                        <div class="card-tools">
                            @if($mode === 'view')
                                <a href="{{ route('teacher.profile.edit') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                            @else
                                <a href="{{ route('teacher.profile') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            @endif
                        </div>
                    </div>
                    <form id="profileForm" data-teacher-id="{{ $teacher->id }}">
                        @csrf
                        <input type="hidden" id="teacherId" value="{{ $teacher->id }}">
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstName">First Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="firstName" 
                                               name="first_name"
                                               value="{{ $teacher->first_name }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="lastName" 
                                               name="last_name"
                                               value="{{ $teacher->last_name }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="middleName">Middle Name</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="middleName" 
                                               name="middle_name"
                                               value="{{ $teacher->middle_name ?? '' }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }} />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" 
                                                id="gender" 
                                                name="gender"
                                                {{ $mode === 'view' ? 'disabled' : '' }}>
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ $teacher->gender === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ $teacher->gender === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ $teacher->gender === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email"
                                               value="{{ $teacher->email }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone"
                                               value="{{ $teacher->phone }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dateJoined">Date Joined</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="dateJoined" 
                                               value="{{ $teacher->created_at ? date('F d, Y', strtotime($teacher->created_at)) : 'N/A' }}"
                                               readonly />
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($mode === 'edit')
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="{{ route('teacher.profile') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Classes Handled Section - Full Width -->
        <div class="row">
            <div class="col-12">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book mr-2"></i>Classes Handled
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0" id="classesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Class Name</th>
                                        <th class="text-center">Sections</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count($classes) > 0)
                                        @foreach($classes as $class)
                                            <tr data-class-id="{{ $class->id }}" data-sections="{{ $class->sections }}">
                                                <td class="text-center">
                                                    <button class="expand-btn" data-class-id="{{ $class->id }}" title="Show Sections">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </td>
                                                <td>{{ $class->class_name }}</td>
                                                <td class="text-center">
                                                    @if($class->sections)
                                                        <span class="badge badge-primary">
                                                            {{ count(explode(', ', $class->sections)) }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary">0</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="no-classes">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                                    <p>No classes assigned</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            updateTeacherProfile: "{{ route('teacher.profile.update') }}",
            redirectBack: "{{ route('teacher.profile') }}"
        };
    </script>
    <script src="{{ asset('js/teacher/teacher_profile.js') }}"></script>
@endsection