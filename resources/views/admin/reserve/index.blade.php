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
    <div class="row">
        {{-- <div class="col-md-3">
            <label for="agent_ids" class="form-label">Agent</label>
            <select class="form-select agent_ids" name="agent" id="agent_ids">
                <!-- Agent options -->
            </select>
        </div> --}}
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
                <table id="example" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Zip</th>
                            <th>State</th>
                            <th>Reason</th>
                            <th>Campaign</th>
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
@include('admin.select2.select2');
<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
@include('admin.select2.scriptload', ['load' => ['datatable']])
<script type="text/javascript">
    $(function() {
        initializeSelect2();
        if ($("#example").length) {
            var table = $('#example').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.reserve.contact') }}",
                    data: function(d) {
                        // d.agent_ids = $('.agent_ids').val() || null;
                        d.state_ids = $('.state_ids').val() || null;
                        d.campaign_ids = $('.campaign_ids').val() || null;
                        d.customDateRange = $('.customDateRange').val() || null;
                    }
                },
                order: [
                    [9, "desc"]
                ], // created_at
                pageLength: 10,
                // dom: 'Bfrtip', // <== Important for button layout
                // buttons: [{
                //         extend: 'csvHtml5',
                //         text: 'CSV', // CSV icon
                //         className: 'btn btn-sm btn-outline-success',
                //         titleAttr: 'Export to CSV',
                //         filename: 'Reserved_contacts_csv',
                //         exportOptions: {
                //             columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] // Adjust columns as needed
                //         }
                //     },
                //     {
                //         extend: 'pdfHtml5',
                //         text: 'PDF', // PDF icon
                //         className: 'btn btn-sm btn-outline-danger',
                //         titleAttr: 'Export to PDF',
                //         filename: 'Reserved_contacts_pdf',
                //         exportOptions: {
                //             columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
                //         }
                //     }
                // ],
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'first_name',
                        name: 'first_name'
                    }, // will contain first_name + last_name
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
                        name: 'city',
                    },
                    {
                        data: 'postal_code',
                        name: 'postal_code',

                    },

                    {
                        data: 'state',
                        name: 'state',

                    },
                    {
                        data: 'reason',
                        name: 'reason',
                    },
                    {
                        data: 'campaign.campaign_name',
                        name: 'campaign.campaign_name'
                    }, // fixed
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
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

        $('body').on('change', '.state_ids, .campaign_ids, .customDateRange', function() {
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

            $('#example').DataTable().ajax.reload(); // Reload table after setting date
        });

        // Clear date range
        $('#customDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $(this).removeAttr("data-start-date");
            $(this).removeAttr("data-end-date");
            $('#example').DataTable().ajax.reload();
        });
    });
</script>
<script>
    $(document).ready(function() {
        $("#userForm").on("submit", function(e) {
            e.preventDefault(); // Prevent default form submission

            let formData = {
                agent_id: $("#agents").val(), // Get selected agent
                lead_id: $("#agents").find(":selected").data("lead"),
                lead_type_id:$("#agents").find(":selected").data("leadtypeid"),
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



    function savaData(id, name, email, state,leadtype,campaignid) {
        console.log(id, name, email, state,leadtype);
        let leadId = id; // Assign leadId

        let url = `/admin/state/reserve/${state}/${leadtype}/${campaignid}`; // Ensure the correct API URL

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
                    $.each(response, function(index, item) {
                        stateDropdown.append(
                            '<option value="' + item.id + '" data-lead="' + leadId + '" data-leadtypeid="' + item.lead_type_id + '">' + item.name + '</option>'
                        );
                    });
                }
                // table.ajax.reload(); // Reload DataTable if necessary
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                toastr.error('An error occurred. Please try again.');
            }
        });
    }
</script>
<script src="{{ asset('assets/js/app.js') }}"></script>
