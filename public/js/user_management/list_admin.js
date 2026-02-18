$(document).ready(function () {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    });

    // ── DataTables ────────────────────────────────────────────────────────────

    const dtDefaults = {
        pageLength: 25,
        language: {
            emptyTable:   'No admins found',
            zeroRecords:  'No matching admins found',
        },
    };

    const activeTable = $('#activeAdminTable').DataTable(Object.assign({}, dtDefaults, {
        order: [[0, 'asc']],
        columnDefs: [{ targets: [5], orderable: false }]
    }));

    const inactiveTable = $('#inactiveAdminTable').DataTable(Object.assign({}, dtDefaults, {
        order: [[0, 'asc']],
        columnDefs: [{ targets: [5], orderable: false }]
    }));

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#activeAdmins')   activeTable.columns.adjust().draw();
        if (target === '#inactiveAdmins') inactiveTable.columns.adjust().draw();
    });

    // ── Edit ──────────────────────────────────────────────────────────────────

    $(document).on('click', '.btn-edit', function () {
        $('#editAdminId').val($(this).data('id'));
        $('#editAdminName').val($(this).data('name'));
        $('#editEmail').val($(this).data('email'));
        $('#editAdminModal').modal('show');
    });

    $('#editAdminForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: API_ROUTES.updateAdmin + '/' + $('#editAdminId').val(),
            method: 'POST',
            data: {
                admin_name: $('#editAdminName').val(),
                email:      $('#editEmail').val()
            },
            success: function (res) {
                if (res.success) {
                    $('#editAdminModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Updated', text: res.message,
                        showConfirmButton: false, timer: 1500 })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            },
            error: function (xhr) {
                const msg = xhr.status === 422
                    ? Object.values(xhr.responseJSON.errors).map(e => e[0]).join('<br>')
                    : (xhr.responseJSON?.message || 'Something went wrong');
                Swal.fire({ icon: 'error', title: 'Error', html: msg });
            }
        });
    });

    // ── Reset Password ────────────────────────────────────────────────────────

    $(document).on('click', '.btn-reset-password', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Reset Password?',
            html: `Reset password for <strong>${name}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Reset',
            confirmButtonColor: '#6c757d',
            cancelButtonColor:  '#6c757d'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: API_ROUTES.resetPassword + '/' + id,
                method: 'POST',
                success: function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Reset',
                            html: `<p>${res.message}</p>
                                   <hr>
                                   <p>New Password: <code>${res.new_password}</code></p>
                                   <p class="text-muted small mt-2">Save this — it won't be shown again.</p>`
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to reset password' });
                }
            });
        });
    });

    // ── Deactivate ────────────────────────────────────────────────────────────

    $(document).on('click', '.btn-deactivate', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Deactivate Admin?',
            html: `Deactivate <strong>${name}</strong>? They will no longer be able to log in.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Deactivate',
            confirmButtonColor: '#6c757d',
            cancelButtonColor:  '#6c757d'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: API_ROUTES.toggleStatus + '/' + id,
                method: 'POST',
                data: { status: 0 },
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Deactivated', text: res.message,
                            showConfirmButton: false, timer: 1500 })
                            .then(() => location.reload());
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to deactivate admin' });
                }
            });
        });
    });

    // ── Activate ──────────────────────────────────────────────────────────────

    $(document).on('click', '.btn-activate', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Activate Admin?',
            html: `Activate <strong>${name}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Activate',
            confirmButtonColor: '#6c757d',
            cancelButtonColor:  '#6c757d'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: API_ROUTES.toggleStatus + '/' + id,
                method: 'POST',
                data: { status: 1 },
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Activated', text: res.message,
                            showConfirmButton: false, timer: 1500 })
                            .then(() => location.reload());
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to activate admin' });
                }
            });
        });
    });

});