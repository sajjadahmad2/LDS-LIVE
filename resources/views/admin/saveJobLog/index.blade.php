
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
                    <li class="breadcrumb-item active" aria-current="page">All Job Logs</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- User Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Contact Id</th>
                            <th>Message</th>

                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(function() {
        if ($("#example").length) {
           $table= $('#example').DataTable({
                processing: true,
                serverSide: false,
                ajax: "{{ route('admin.job.logs') }}",
 // Sorting by 'created_at' column (index 5) in descending order
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'contact_id',
                        name: 'contact_id'
                    },
                    {
                        data: 'message',
                        name: 'contact_id'
                    },


                ]
            });
        } else {
            console.error("Table #example not found.");
        }
    });
</script>
