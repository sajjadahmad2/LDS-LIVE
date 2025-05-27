@extends('admin.layouts.index')

    @section('css')
    <style>
        .states-column {
            max-height: 50px;
            /* Adjust height as needed */
            overflow-y: auto;
            display: block;
            white-space: nowrap;
            max-width: 390px;
        }
            
    @media (max-width: 500px) {
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    }
    </style>
@endsection


@section('content')
    <!-- Page Breadcrumb -->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Dashboard</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:;"></a></li>
                    <li class="breadcrumb-item active" aria-current="page">All CustomFields</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Add User Button -->
<div class="container mt-4">
    <div class="row d-flex justify-content-between g-0">
        <!-- Assign Agent Form -->
        <div class="col-md-4 d-flex">
            <div class="card p-3 shadow flex-fill">
                <h5 class="mb-3">Change Agent</h5>
                <form id="assignAgentForm">
                    @csrf
                    <div class="mb-3">
                        <label for="contact_id" class="form-label">Contact ID</label>
                        <input type="number" class="form-control" id="contact_id" name="contact_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="agent_id" class="form-label">Agent ID</label>
                        <input type="number" class="form-control" id="agent_id" name="agent_id" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Change Agent</button>
                </form>
            </div>
        </div>

        <!-- Sync Custom Fields Form -->
        <div class="col-md-4 d-flex">
            <div class="card p-3 shadow flex-fill">
                <h5 class="mb-3">Sync Custom Fields</h5>
                <form id="syncCustomFieldsForm">
                    <div class="mb-3">
                        <label for="location_id" class="form-label">Enter Location ID</label>
                        <input type="text" id="location_id" name="location_id" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sync Custom Fields</button>
                </form>
            </div>
        </div>

        <!-- Update Customfield Name -->
        <div class="col-md-4 d-flex">
            <div class="card p-3 shadow flex-fill">
                <h5 class="mb-3">Update Customfield Name</h5>
                <form id="updateCustomFieldForm">
                    @csrf
                    <div class="form-group">
                        <label for="customfield_id">GHL Custom Field ID</label>
                        <input type="text" id="customfield_id" name="customfield_id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="customfield_name">Custom Field Name</label>
                        <input type="text" id="customfield_name" name="customfield_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>


    <hr />
    @php $user = auth()->user()->load('ghlauth'); $company = isset($user->ghlauth[0]) ? $user->ghlauth[0]->user_id : null; @endphp @if($user && $company) <form method="POST" action="{{url('admin/fetch/alluserlocation')}}"> @csrf <button type="submit" class="btn btn-success text-center text-dark my-3 mx-3"> Sync Location and CustomField </button> </form> @else @endif
    <!-- User Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Field id</th>
                            <th>Name</th>
                            <th>Key</th>
                            <th>Data Type</th>
                            <th>Location_id</th>

                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<!-- JS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(function() {
        if ($("#example").length) {
           $table= $('#example').DataTable({
                processing: true,
                serverSide: false,
                ajax: "{{ route('admin.customfield.index') }}",
 // Sorting by 'created_at' column (index 5) in descending order
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'cf_id',
                        name: 'cf_id'
                    },
                    {
                        data: 'cf_name',
                        name: 'cf_name'
                    },
                    {
                        data: 'cf_key',
                        name: 'cf_key',
                        render: function(data, type, row) {
                            return `<div class="states-column">${data}</div>`;
                        }
                    },
                    {
                        data: 'dataType',
                        name: 'dataType'
                    },
                    {
                        data: 'location_id',
                        name: 'location_id',
                    },

                ]
            });
        } else {
            console.error("Table #example not found.");
        }
    });
</script>
<script>
 $(document).ready(function () {
        $("#assignAgentForm").submit(function (e) {
            e.preventDefault();

            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be reverted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, update it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = $(this).serialize();
                    
                    $.ajax({
                        url: "{{ route('admin.change.agent.id') }}",
                        type: "POST",
                        data: formData,
                        success: function (response) {
                            Swal.fire("Success!", response.message, "success");
                            $("#assignAgentForm")[0].reset();
                        },
                        error: function (xhr) {
                            Swal.fire("Error!", xhr.responseJSON.message, "error");
                        }
                    });
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function () {
        $('#syncCustomFieldsForm').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission
            
            let locationId = $('#location_id').val();
            
            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be reverted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, sync it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.sync.custom.fields') }}",
                        type: "POST",
                        data: {
                            location_id: locationId,
                            _token: "{{ csrf_token() }}"
                        },
                        dataType: "json",
                        success: function (response) {
                            Swal.fire("Success", response.message, "success");
                        },
                        error: function (xhr) {
                            let errorMsg = "Something went wrong!";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            Swal.fire("Error", errorMsg, "error");
                        }
                    });
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function () {
        $(document).off('submit', '#updateCustomFieldForm').on('submit', '#updateCustomFieldForm', function (e) {
            e.preventDefault(); // Prevents the form from submitting normally

            Swal.fire({
                title: "Are you sure?",
                text: "Do you really want to update this custom field?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, update it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.update.custom.field') }}",
                        type: "POST",
                        data: {
                            customfield_id: $('#customfield_id').val(),
                            customfield_name: $('#customfield_name').val(),
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function () {
                            Swal.fire({
                                title: "Updating...",
                                text: "Please wait while we update the custom field.",
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                willOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function (response) {
                            Swal.fire("Success", response.message, "success");
                            $('#example').DataTable().ajax.reload(null, false);
                                                // 🔄 Reset the form
                            $('#updateCustomFieldForm')[0].reset();

                        },
                        error: function (xhr) {
                            Swal.fire("Error", "Something went wrong!", "error");
                        }
                    });
                }
            });
        });
    });
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
