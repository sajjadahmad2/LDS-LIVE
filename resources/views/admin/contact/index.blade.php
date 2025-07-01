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
                    <li class="breadcrumb-item active" aria-current="page">Sent Contact</li>
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
                            <th>Agent</th>
                            <th>Campaign</th>
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
<script type="text/javascript">
    $(function() {
        if ($("#example").length) {
           $('#example').DataTable({
            processing: true,
            serverSide: true, // Enable server-side processing
            ajax: "{{ route('admin.sent.contact') }}", // Fetch data from Laravel
            order: [[5, "desc"]], // Sort by created_at in descending order
            pageLength: 10, // Load in chunks of 10
            columns: [
                { data: 'id', name: 'id' },
                { data: 'contact_id', name: 'contact_id' },
                { data: 'first_name', name: 'first_name' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                { data: 'state', name: 'state' },
                { data: 'created_at', name: 'created_at' },
                { data: 'agent_id', name: 'agent_id' },
                { data: 'campaign_id', name: 'campaign_id' },
            ]
        });
        } else {
            console.error("Table #example not found.");
        }
    });
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
