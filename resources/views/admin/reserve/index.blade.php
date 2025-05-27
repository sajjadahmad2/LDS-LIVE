@extends('admin.layouts.index')

<style>
    /* Custom CSS for responsive table */
    .table-responsive {
        overflow-x: hidden !important;
    }

    @media (max-width: 500px) {
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
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
                    <li class="breadcrumb-item active" aria-current="page">Reserve Contact</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Add User Button -->

    <hr />

    <!-- User Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Contact Id</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>State</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                    <h5 class="modal-title" id="exampleModalLabel">Choose Agent to Send</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" method="POST" class="row g-3">
                        @csrf <!-- Laravel CSRF token for security -->
                        <div class="card-body">
                            <!-- State -->
                            <div class="col-12">
                                <label for="agents" class="form-label">Agents</label>
                                <select class="form-control" id="agents" name="agents">
                                    <!-- Dynamic options will be added via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success px-5" id="submitButton">Send</button>
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
        if ($("#example").length) {
            $('#example').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.reserve.contact') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    { data: 'contact_id', name: 'contact_id' },
                    {
                        data: 'first_name',
                        name: 'first_name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone',
                        name: 'phone'
                    },
                    {
                        data: 'state',
                        name: 'state'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            return moment(data).format('YYYY-MM-DD hh:mm A'); 
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        } else {
            console.error("Table #example not found.");
        }
    });

    $(document).ready(function() {
    $("#userForm").on("submit", function(e) {
        e.preventDefault(); // Prevent default form submission

        let formData = {
            agent_id: $("#agents").val(), // Get selected agent
            lead_id: $("#agents").find(":selected").data("lead"),
            _token: $('input[name="_token"]').val() // Get CSRF token
        };

        $.ajax({
            url: "/admin/assign-agent",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $("#submitButton").prop("disabled", true).text("Saving...");
            },
            success: function(response) {
                console.log("Success:", response);
                toastr.success("Agent assigned successfully!");
                $("#submitButton").prop("disabled", false).text("Save");

                // Optionally, reload DataTable or reset form
                $('#example').DataTable().ajax.reload();
                $("#userForm")[0].reset();
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                toastr.error("An error occurred. Please try again.");
                $("#submitButton").prop("disabled", false).text("Save");
            }
        });
    });
});



    function savaData(id, name, email, state) {
        console.log(id, name, email, state);
        let leadId = id; // Assign leadId

        let url = `/admin/state/reserve/${state}`; // Ensure the correct API URL

        $.ajax({
            url: url,
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Ensure CSRF token is included
            },
            success: function(response) {
                var stateDropdown = $('#agents');
                stateDropdown.empty(); // Clear previous options

                if ($.isEmptyObject(response)) {
                    stateDropdown.append('<option value="">No agents found</option>');
                } else {
                    $.each(response, function(id, name) {
                        stateDropdown.append('<option value="' + id + '" data-lead="' + leadId +
                            '">' + name + '</option>');
                    });
                }
                table.ajax.reload(); // Reload DataTable if necessary
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                toastr.error('An error occurred. Please try again.');
            }
        });
    }
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
