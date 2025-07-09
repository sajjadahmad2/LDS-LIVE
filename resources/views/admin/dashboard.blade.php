@extends('admin.layouts.index')
@section('content')
@if( is_role() == 'admin')
    <div class="row row-cols-md-2 row-cols-xl-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label for="agentSelect" class="form-label">Agent</label>
                    @if ($agents->count() > 0)
                        <select class="form-select" id="agentSelect" data-type="agent" onchange="fetchData(this)">
                            <option value="all">Please Select Agent</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <p>No agents available.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label for="campaignSelect" class="form-label">Campaign</label>

                    @if (isset($campaigns) && $campaigns->count() > 0)
                        <select class="form-select" id="campaignSelect" data-type="campaign" onchange="fetchData(this)">
                            <option value="all">Please Select Campaign</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>
                            @endforeach
                        </select>
                    @else
                        <p>No campaigns available.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label for="dataRangeSelect" class="form-label">Date Range Select</label>
                    <input type="text" name="datefilter" value="" class="form-control" />
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table class="table mb-0 table-hover table-order">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Total Limit / Sent</th>
                                <th scope="col">Total Remaining</th>
                                <th scope="col">Daily Limit / Sent</th>
                                <th scope="col">Monthly Limit / Sent</th>
                                <th scope="col">Priority</th>
                            </tr>
                        </thead>
                        <tbody id="dataBody">
                            @if (!empty($data) && $data->count() > 0)
                                @foreach ($data as $agent)
                                    <tr>
                                        <td> {{ $loop->index + 1 }}</td>
                                        <td> {{ $agent->name }}</td>
                                        <td>{{ $agent->total_limit }}/{{ $agent->total_contacts_count }}</td>
                                        <td>{{ $agent->total_limit-$agent->total_contacts_count }}</td>
                                        <td>{{ $agent->daily_limit }}/{{ $agent->daily_contacts_count }}</td>
                                        <td>{{ $agent->monthly_limit }}/{{ $agent->monthly_contacts_count }}</td>
                                        <td>{{ $agent->priority }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <p>No Data< is Found</p>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    <a href="javascript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
@endsection

@section('scripts')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        function fetchData(selectElement) {
            const selectedValue = selectElement.value;
            const type = selectElement.dataset.type; // 'agent' or 'campaign'
            const dateRange = encodeURIComponent($('input[name="datefilter"]').val());
            let agentId = $('#agentSelect').val();
            let campaignId = $('#campaignSelect').val();

            let url = '/admin/compaign/agent';
            // Clear the other dropdown if one of them is selected
            if (type === 'agent' && agentId) {
                document.getElementById('campaignSelect').value = 'all';
                campaignId = $('#campaignSelect').val()
            } else if (type === 'campaign' && campaignId) {
                document.getElementById('agentSelect').value = 'all';
                agentId = $('#agentSelect').val();
            }
            console.log(agentId, campaignId, type, dateRange);
         // Construct query parameters for agentId, campaignId, type, and dateRange
         let params = `?dateRange=${dateRange}&type=${type}`; // Add 'type' here

            if (agentId) {
                params += `&agentId=${agentId}`;
            }

            if (campaignId) {
                params += `&campaignId=${campaignId}`;
            }
            // // If no value is selected and no date range is provided, display a message
            // if (!selectedValue && !dateRange) {
            //     $("#dataBody").html("<tr><td colspan='6'>Please select a valid option.</td></tr>");
            //     return;
            // }

            $.ajax({
                type: "GET",
                url: url + params, // Send the URL with the query parameters
                success: function(response) {
                    let tableRows = '';
                    if (response.success) {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }
                        if (response.data !== null) {
                            response.data.forEach((item, index) => {
                                tableRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.name}</td>
                            <td>${item.total_limit || 0} / ${item.total_contacts_count || 0}</td>
                            <td>${item.total_limit-item.total_contacts_count || 0}</td>
                            <td>${item.daily_limit || 0} / ${item.daily_contacts_count || 0}</td>
                            <td>${item.monthly_limit || 0} / ${item.monthly_contacts_count || 0}</td>
                            <td>${item.priority || 'N/A'}</td>
                        </tr>`;
                            });
                        } else {
                            tableRows = `<tr><td colspan="6">No ${type} data found.</td></tr>`;
                        }
                        $("#dataBody").html(tableRows);
                    }
                },
                error: function(xhr) {
                    console.error("Error:", xhr.responseText);
                    alert("Failed to fetch data. Please try again later.");
                    $("#dataBody").html("<tr><td colspan='6'>An error occurred.</td></tr>");
                }
            });
        }


        $(function() {
            $('input[name="datefilter"]').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            // Handle apply date range
            $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format(
                    'MM/DD/YYYY'));
                if ($('#agentSelect').val() || $('#campaignSelect').val() || $(this).val()) {
                    fetchData(document.getElementById('agentSelect'));
                    fetchData(document.getElementById('campaignSelect'));
                }
            });

            // Handle cancel date range
            $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                if ($('#agentSelect').val() || $('#campaignSelect').val() || !$(this).val()) {
                    fetchData(document.getElementById(
                    'agentSelect')); // Trigger the fetchData function when date range is cleared
                    fetchData(document.getElementById(
                    'campaignSelect')); // Trigger the fetchData function when date range is cleared
                }
            });
        });
    </script>
@endsection
