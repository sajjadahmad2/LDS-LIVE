@extends('admin.layouts.index')

<style>
    /* Custom CSS for responsive table */
    .table-responsive {
        overflow-x: hidden !important;
        /* Default: Hide horizontal overflow */
    }

    @media (max-width: 500px) {
        .table-responsive {
            overflow-x: auto !important;
            /* Allow horizontal scrolling on smaller screens */
            -webkit-overflow-scrolling: touch;
            /* Enable smooth scrolling for touch devices */
        }
    }
</style>

@section('content')
    <!-- Page Breadcrumb -->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Dashboard</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:;"></a></li>
                    <li class="breadcrumb-item active" aria-current="page">User</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Add User Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"
        onclick="savaData('0','','','','','')">Add User</button>
    <hr />


    <!-- User Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="row row-cols-auto">
        <div class="col">
            <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <!-- Loader -->
                <div class="modal-dialog">
                    <div class="lds-roller loader" style="display: none;">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">User Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="errorMessages"></div>

                            <form id="userForm" method="POST" class="row g-3">
                                @csrf <!-- Laravel CSRF token for security -->
                                <div class="card-body">
                                    <!-- Name -->
                                    <div class="col-12">
                                        <label for="inputFirstName" class="form-label">Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class="bx bxs-user"></i></span>
                                            <input type="hidden" class="form-control" id="user_id" name="id">
                                            <input type="text" id="name" name="name"
                                                class="form-control border-start-0" placeholder="Enter Name">
                                        </div>
                                    </div>
                                    <!-- Email -->
                                    <div class="col-12">
                                        <label for="inputEmailAddress" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bx bxs-message"></i></span>
                                            <input type="email" id="email" name="email"
                                                class="form-control border-start-0" placeholder="Email Address">
                                        </div>
                                    </div>
                                    <!-- Password -->
                                    <div class="col-12">
                                        <label for="inputLastName" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bx bxs-lock-open"></i></span>
                                            <input type="password" id="password" class="form-control border-start-0"
                                                name="password" placeholder="Choose Password">
                                        </div>
                                    </div>
                                    <!-- Location ID -->
                                    <div class="col-12">
                                        <label for="location_id" class="form-label">Location Id</label>
                                        <input type="text" id="location_id" name="location_id" class="form-control"
                                            placeholder="Enter Location Id">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-success px-5" id="submitButton">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endsection

        <!-- JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <script type="text/javascript">
            $(function() {
                // DataTable initialization
                var table = $('.data-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('admin.user.index') }}",
                    columns: [{
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'email',
                            name: 'email'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ]
                });

                // Handle form submission with AJAX
                $("form#userForm").submit(function(event) {
                    event.preventDefault();
                    let formData = $(this).serialize();
                    $(".lds-roller").show(); // Show the loader
                    $("#errorMessages").empty();
                    // $("#submitButton").replaceWith(
                    //     '<button class="btn btn-success" type="button" disabled>Loading...</button>');

                    // AJAX request for form submission
                    $.ajax({
                        type: "POST",
                        url: "{{ route('admin.user.store') }}", // Update with the correct route
                        data: formData,
                        success: function(response) {
                            toastr.success("User saved successfully!");
                            table.ajax.reload(); // Reload the table
                            $('#userModal').modal('hide'); // Close modal
                            $(".lds-roller").hide(); // Hide the loader
                            //$("#submitButton").replaceWith('<button type="submit" class="btn btn-success">Save</button>');
                        },
                        error: function(xhr) {
            $(".lds-roller").hide();
            //$("#submitButton").replaceWith('<button type="submit" class="btn btn-success">Save</button>');

            if (xhr.status === 422) {
                // Display validation errors
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    $("#errorMessages").append('<div class="alert alert-danger">' + value[0] + '</div>');
                });
            } else {
                toastr.error("An error occurred. Please try again.");
            }
        }
                    });
                });

                // Delete User
                $('body').on('click', '.confirm-delete', function(e) {
                    e.preventDefault();
                    let userId = $(this).data('id');
                    let url = '{{ route('admin.user.destroy', ':id') }}'.replace(':id', userId);

                    if (confirm("Are you sure you want to delete this user?")) {
                        $.ajax({
                            url: url,
                            type: "DELETE",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                toastr.success('User deleted successfully!');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error('An error occurred. Please try again.');
                            }
                        });
                    }
                });

                // Change User Status
                $('body').on('click', '.status_changes', function(e) {
                    e.preventDefault();
                    const id = $(this).data('status');
                    const url = "{{ route('admin.user.status', ':id') }}".replace(':id', id);

                    if (confirm("Are you sure you want to change the status of this user?")) {
                        $.ajax({
                            type: "GET",
                            url: url,
                            success: function(response) {
                                toastr.success(response.message ||
                                    'User status changed successfully!');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error('An error occurred while updating the status.');
                            }
                        });
                    }
                });

                //Partial done
                $('body').on('click', '.fetch_customField', function(e){
                    fetchCustom_field = $(this).data('customField');

                });
            });

            // Pre-fill form data
            function savaData(id, name, email, password, role, location_id) {
                $('#user_id').val(id);
                $('#name').val(name);
                $('#email').val(email);
                $('#password').val('');
                $('#role').val(role);
                $('#location_id').val(location_id);
            }
        </script>
        <script src="{{ asset('assets/js/app.js') }}"></script>
    </div>
</div>
