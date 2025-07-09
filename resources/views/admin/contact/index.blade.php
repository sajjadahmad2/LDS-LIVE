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

    .card {
        border-radius: 12px;
        background-color: #f8f9fa;
    }

    .form-label {
        font-weight: 500;
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
    <div class="row">
        <div class="col-md-3">
            <label for="agent_ids" class="form-label">Agent</label>
            <select class="form-select agent_ids" name="agent" id="agent_ids">
                <!-- Agent options -->
            </select>
        </div>
        <div class="col-md-3">
            <label for="state_ids" class="form-label">State</label>
            <select class="form-select state_ids" name="state" id="state_ids">
                <!-- State options -->
            </select>
        </div>
        <div class="col-md-3">
            <label for="campaign_ids" class="form-label">Campaign</label>
            <select class="form-select campaign_ids" name="campaign" id="campaign_ids">
                <!-- Campaign options -->
            </select>
        </div>
        <div class="col-md-3">
            <label for="customDateRange" class="form-label">Date Range</label>
            <input type="text" name="date_range" id="customDateRange" class="form-control customDateRange" />
        </div>
    </div>


    <hr />

    <!-- User Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="sent-contact" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Zip</th>
                            <th>State</th>
                            <th>Full Address</th>
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

@include('admin.select2.select2');
<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
@include('admin.select2.scriptload', ['load' => ['datatable']])
<script type="text/javascript">
    $(function() {
        initializeSelect2();
        if ($("#sent-contact").length) {
            var table = $('#sent-contact').DataTable({
                processing: true,
                serverSide: true, // Enable server-side processing
                ajax: {
                    "url": "{{ route('admin.sent.contact') }}",
                    data: function(d) {
                        d.agent_ids = $('.agent_ids').val() || null;
                        d.state_ids = $('.state_ids').val() || null;
                        d.campaign_ids = $('.campaign_ids').val() || null;
                        d.customDateRange = $('.customDateRange').val() || null;
                    }
                }, // Fetch data from Laravel
                order: [
                    [8, "desc"]
                ], // Sort by created_at in descending order
                pageLength: 10, // Load in chunks of 10
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
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
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'postal_code',
                        name: 'postal_code'
                    },
                    {
                        data: 'state',
                        name: 'state'
                    },
                    {
                        data: 'full_address',
                        name: 'full_address'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'agent_id',
                        name: 'agent_id'
                    },
                    {
                        data: 'campaign_id',
                        name: 'campaign_id'
                    },
                ]
            });
        } else {
            console.error("Table #example not found.");
        }
        $('body').on('change', '.agent_ids,.state_ids, .campaign_ids, .customDateRange', function() {
            table.ajax.reload();
        });
        $('#customDateRange').daterangepicker({
            opens: 'right',
            autoUpdateInput: false,
            locale: {
                format: 'MM/DD/YYYY',
                cancelLabel: 'Clear'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    'month').endOf(
                    'month')]
            }
        });
        // Set the selected date range in the input field
        $('#customDateRange').on('apply.daterangepicker', function(ev, picker) {
            let startDate = picker.startDate.format('YYYY-MM-DD');
            let endDate = picker.endDate.format('YYYY-MM-DD');

            $(this).val(startDate + ' to ' + endDate);
            $(this).attr("data-start-date", startDate);
            $(this).attr("data-end-date", endDate);
            console.log("Start Date:", startDate);
            console.log("End Date:", endDate);

            $('#sent-contact').DataTable().ajax.reload(); // Reload table after setting date
        });

        // Clear date range
        $('#customDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $(this).removeAttr("data-start-date");
            $(this).removeAttr("data-end-date");
            $('#sent-contact').DataTable().ajax.reload();
        });
    });
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
