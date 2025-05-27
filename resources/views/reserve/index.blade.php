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
                    <li class="breadcrumb-item active" aria-current="page">User</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Add User Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"
        onclick="savaData('0','','','')">Add User</button>
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
                            <th>Phone</th>
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
                <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">User Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" method="POST" class="row g-3">
                        @csrf <!-- Laravel CSRF token for security -->
                        <div class="card-body">
                            <!-- Name -->
                            <div class="col-12">
                                <label for="inputFirstName" class="form-label">Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bx bxs-user"></i></span>
                                    <input type="hidden" class="form-control" id="user_id" name="id">
                                    <input type="text" id="name" name="name" class="form-control border-start-0" placeholder="Enter Name">
                                </div>
                            </div>
                            <!-- Email -->
                            <div class="col-12">
                                <label for="inputEmailAddress" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="bx bxs-message"></i></span>
                                    <input type="email" id="email" name="email" class="form-control border-start-0" placeholder="Email Address">
                                </div>
                            </div>
                            
                            <!-- State -->
                            <div class="col-12">
                                <label for="state" class="form-label">State</label>
                                <select class="form-control" id="state" name="state">
                                    <!-- Dynamic options will be added via JavaScript -->
                                </select>
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
        if ($("#example").length) {
            $('#example').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.reserve.contact') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'first_name', name: 'first_name' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        } else {
            console.error("Table #example not found.");
        }
    });

    // Pre-fill form data
    function savaData(id, first_name, email, state) {
        // Fill the form fields with the data
        $('#user_id').val(id);
        $('#name').val(first_name);
        $('#email').val(email);

        // Fill the state dropdown dynamically
        var stateDropdown = $('#state');
        stateDropdown.empty(); // Clear previous options

        // Fetch agents from the backend (assuming the agents data is available)
        var agents = <?php echo json_encode(\App\Models\Agent::pluck('name', 'id')); ?>;

        // Add the dynamic options
        $.each(agents, function(id, name) {
            var selected = (id == state) ? 'selected' : ''; // Mark as selected if the state matches the passed state
            stateDropdown.append('<option value="' + id + '" ' + selected + '>' + name + '</option>');
        });
    }
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
