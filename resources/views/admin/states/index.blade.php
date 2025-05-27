@extends('admin.layouts.index')
@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Dashboard</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:;"></a></li>
                    <li class="breadcrumb-item active" aria-current="page">States</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stateModal"
        onclick="savaStateData(0, '', '')">Add State</button>
    <hr />

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="statesTable" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>State</th>
                            <th>Location ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="stateModal" tabindex="-1" aria-labelledby="stateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stateModalLabel">Add/Edit State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stateForm" method="POST">
                        @csrf
                        <input type="hidden" id="state_id" name="id">

                        <div class="mb-3">
                            <label for="state" class="form-label">State Name</label>
                            <input type="text" id="state" name="state" class="form-control" placeholder="Enter State Name" required>
                        </div>

                        <div class="mb-3">
                            <label for="location_id" class="form-label">Location ID</label>
                            <input type="text" id="location_id" name="location_id" class="form-control" placeholder="Enter Location ID" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript">
    $(function() {
        // Initialize DataTable
        var table = $('#statesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.states.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'state', name: 'state' },
                { data: 'location_id', name: 'location_id' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        // Handle Add/Edit form submission
        $('#stateForm').submit(function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let url = $('#state_id').val()
                ? "{{ route('admin.states.update', ':id') }}".replace(':id', $('#state_id').val())
                : "{{ route('admin.states.store') }}";
            let method = $('#state_id').val() ? 'PUT' : 'POST';

            $.ajax({
                type: method,
                url: url,
                data: formData,
                success: function(response) {
                    $('#stateModal').modal('hide');
                    $('#stateForm')[0].reset();
                    table.ajax.reload();
                },
                error: function(xhr) {
                    console.error(xhr.responseJSON.message);
                }
            });
        });

        // Pre-fill the modal form for editing
        window.savaStateData = function(id, state, location_id) {
            $('#state_id').val(id);
            $('#state').val(state);
            $('#location_id').val(location_id);
        };

        // Handle Delete
        window.deleteState = function(stateId) {
            if (confirm("Are you sure you want to delete this state?")) {
                $.ajax({
                    url: "{{ route('admin.states.destroy', ':id') }}".replace(':id', stateId),
                    type: 'DELETE',
                    data: { "_token": "{{ csrf_token() }}" },
                    success: function(response) {
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseJSON.message);
                    }
                });
            }
        };
    });
</script>
@endsection
