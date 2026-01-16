@extends('layouts.main-guardian')

@section('styles')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-user breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('guardian.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">My Profile</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- Profile Image Card -->
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle" 
                         src="{{ $guardian->profile_image ? asset('storage/' . $guardian->profile_image) : asset('img/default-avatar.png') }}" 
                         alt="Guardian profile picture"
                         style="width: 120px; height: 120px; object-fit: cover;"
                         id="profileImagePreview">
                </div>

                <h3 class="profile-username text-center">
                    {{ $guardian->first_name }} {{ $guardian->middle_name }} {{ $guardian->last_name }}
                </h3>
                <p class="text-muted text-center">{{ $guardian->relationship }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                @if($mode === 'view')
                    <a href="{{ route('guardian.profile.edit') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-edit mr-2"></i>Edit Profile
                    </a>
                @else
                    <button type="button" class="btn btn-primary btn-block" id="saveProfileBtn">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="{{ route('guardian.profile') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>
            <div class="card-body">
                <form id="profileForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name"
                                       value="{{ $guardian->first_name }}"
                                       {{ $mode === 'view' ? 'readonly' : '' }}
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="middle_name" 
                                       name="middle_name"
                                       value="{{ $guardian->middle_name }}"
                                       {{ $mode === 'view' ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name"
                                       value="{{ $guardian->last_name }}"
                                       {{ $mode === 'view' ? 'readonly' : '' }}
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="relationship">Relationship <span class="text-danger">*</span></label>
                                <select class="form-control" 
                                        id="relationship" 
                                        name="relationship"
                                        {{ $mode === 'view' ? 'disabled' : '' }}
                                        required>
                                    <option value="Parent" {{ $guardian->relationship == 'Parent' ? 'selected' : '' }}>Parent</option>
                                    <option value="Guardian" {{ $guardian->relationship == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                    <option value="Grandparent" {{ $guardian->relationship == 'Grandparent' ? 'selected' : '' }}>Grandparent</option>
                                    <option value="Sibling" {{ $guardian->relationship == 'Sibling' ? 'selected' : '' }}>Sibling</option>
                                    <option value="Other" {{ $guardian->relationship == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email"
                                       value="{{ $guardian->email }}"
                                       {{ $mode === 'view' ? 'readonly' : '' }}
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone"
                                       value="{{ $guardian->phone }}"
                                       {{ $mode === 'view' ? 'readonly' : '' }}
                                       required>
                            </div>
                        </div>
                    </div>

                    @if($mode === 'edit')
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="profile_image" 
                                   name="profile_image"
                                   accept="image/*">
                            <label class="custom-file-label" for="profile_image">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Accepted formats: JPG, PNG, GIF (Max: 2MB)</small>
                    </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Linked Students -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Linked Students</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Level</th>
                            <th>Section</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{ $student->student_number }}</td>
                            <td>{{ $student->full_name }}</td>
                            <td>{{ $student->level }}</td>
                            <td>{{ $student->section }}</td>
                            <td>
                                <a href="{{ route('guardian.student.grades', $student->student_number) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Grades
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
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(document).ready(function() {
    @if($mode === 'edit')
    // Preview image before upload
    $('#profile_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#profileImagePreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
            
            // Update label
            const fileName = file.name;
            $(this).next('.custom-file-label').html(fileName);
        }
    });

    // Save profile changes
    $('#saveProfileBtn').on('click', function(e) {
        e.preventDefault();
        
        const formData = new FormData($('#profileForm')[0]);
        const $btn = $(this);
        const originalText = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '{{ route("guardian.profile.update") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '{{ route("guardian.profile") }}';
                    });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Please correct the following errors:\n\n';
                    
                    $.each(errors, function(field, messages) {
                        errorMessage += '- ' + messages[0] + '\n';
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to update profile. Please try again.'
                    });
                }
            }
        });
    });
    @endif
});
</script>
@endsection