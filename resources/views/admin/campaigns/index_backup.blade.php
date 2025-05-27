@extends('admin.layouts.index')

@section('css')
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Dashboard</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:;"></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Campaigns</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- End Breadcrumb -->

    <button type="button" class="btn btn-primary me-2 mb-3" data-bs-toggle="modal" data-bs-target="#campaignModal"
        onclick="savaCampaignData(0, '', '', '', '','', [], [])">Add Campaign</button>
    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#copyWebhookModal">
        Copy Webhook URL
    </button>
    <hr />

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="campaignsTable" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign Name</th>
                            <th>Priority</th>
                            <th>Daily Limit</th>
                            <th>Monthly Limit</th>
                            <th>Agents</th>
                            <th>Carrier Types</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Copy Webhook Modal -->
    <div class="modal fade" id="copyWebhookModal" tabindex="-1" aria-labelledby="copyWebhookModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="copyWebhookModalLabel">Copy Webhook URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="campaignDropdown">Select Campaign</label>
                        <select id="campaignDropdown" class="form-select">
                            <option value="">-- Select a Campaign --</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="webhookUrl">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" id="webhookUrl" class="form-control" readonly>
                            <button class="btn btn-primary" id="copyWebhookBtn">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="campaignModal" tabindex="-1" aria-labelledby="campaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="campaignModalLabel">Add/Edit Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="campaignForm" method="POST">
                        @csrf
                        <input type="hidden" id="campaign_id" name="id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="campaign_name" class="form-label">Campaign Name</label>
                                <input type="text" id="campaign_name" name="campaign_name" class="form-control"
                                    placeholder="Enter Campaign Name" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option selected disabled value="">Please Enter Priority...</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="daily_limit" class="form-label">Daily Limit</label>
                                <input type="number" id="daily_limit" name="daily_limit" class="form-control"
                                    placeholder="Enter Daily Limit">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="monthly_limit" class="form-label">Monthly Limit</label>
                                <input type="number" id="monthly_limit" name="monthly_limit" class="form-control"
                                    placeholder="Enter Monthly Limit">
                            </div>
                        </div>
                        <div class="row">

                            <!-- Total  Limit -->
                            <div class="col-md-6 mb-3">
                                <label for="daily_limit" class="form-label">Total Limit</label>
                                <input type="number" id="total_limit" name="total_limit" class="form-control"
                                    placeholder="Enter Total Limit">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="carrier_type" class="form-label">Carrier Type</label>
                                <select id="carrier_type" name="carrier_type[]" class="form-select" multiple="multiple"
                                    style="width: 100%;">
                                    @foreach (getCarrierType() as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="agents" class="form-label">Agents</label>
                                <select id="agents" name="agents[]" class="form-select" multiple="multiple"
                                    style="width: 100%;" required onchange="getAgent(this, '')">
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3" id="user-fields">
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
    <script type="text/javascript">
        $(document).ready(function() {
            $('#agents, #carrier_type').select2({
                dropdownParent: $('#campaignModal'),
                width: '100%'
            });

            var table = $('#campaignsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.campaigns.index') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'campaign_name',
                        name: 'campaign_name'
                    },
                    {
                        data: 'priority',
                        name: 'priority'
                    },
                    {
                        data: 'daily_limit',
                        name: 'daily_limit'
                    },
                    {
                        data: 'monthly_limit',
                        name: 'monthly_limit'
                    },
                    {
                        data: 'agents',
                        name: 'agents'
                    },
                    {
                        data: 'carrier_type',
                        name: 'carrier_type'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#campaignForm').submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                let campaignId = $('#campaign_id').val();
                let method = campaignId && campaignId != "0" ? 'PUT' : 'POST';
                let url = campaignId && campaignId != "0" ?
                    "{{ route('admin.campaigns.update', ':id') }}".replace(':id', campaignId) :
                    "{{ route('admin.campaigns.store') }}";

                $.ajax({
                    type: method,
                    url: url,
                    data: formData,
                    success: function(response) {
                        $('#campaignModal').modal('hide');
                        $('#campaignForm')[0].reset();
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseJSON.message);
                    }
                });
            });

            window.savaCampaignData = function(id, campaign_name, priority, daily_limit, monthly_limit, total_limit,
                carrier_type, agents, weightage) {

                $('#campaign_id').val(id);
                $('#campaign_name').val(campaign_name);
                $('#priority').val(priority);
                $('#daily_limit').val(daily_limit);
                $('#monthly_limit').val(monthly_limit);
                $('#total_limit').val(total_limit);
                $('#carrier_type').val(carrier_type).trigger('change');
                $('#agents').val(agents).trigger('change');
                getAgent(document.getElementById('agents'), agents,
                    ); // Pass agents and weightage to the getAgent function
                $('#campaignModal').modal('show');
            };

            window.deleteCampaign = function(campaignId) {
                if (confirm("Are you sure you want to delete this campaign?")) {
                    $.ajax({
                        url: "{{ route('admin.campaigns.destroy', ':id') }}".replace(':id', campaignId),
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
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
        $(document).ready(function() {
            const campaignDropdown = $('#campaignDropdown');
            const webhookUrlInput = $('#webhookUrl');
            const copyWebhookBtn = $('#copyWebhookBtn');

            const userId = "{{ auth()->user()->location_id ?? auth()->user()->id }}";
            const baseApiRoute =
                "{{ route('api.webhook', ['user_id' => ':user_id', 'campaign_id' => ':campaign_id']) }}";

            // Handle Campaign Selection
            campaignDropdown.on('change', function() {
                const campaignId = $(this).val();
                const encodedid = btoa(campaignId).slice(0, 40)
                if (campaignId) {
                    const webhookUrl = baseApiRoute
                        .replace(':user_id', userId)
                        .replace(':campaign_id', encodedid);
                    webhookUrlInput.val(webhookUrl);
                } else {
                    webhookUrlInput.val('');
                }
            });

            // Copy to Clipboard with Toastr Notification
            copyWebhookBtn.on('click', function() {
                const webhookUrl = webhookUrlInput.val();
                if (webhookUrl) {
                    navigator.clipboard.writeText(webhookUrl).then(() => {
                        toastr.success("Webhook URL copied to clipboard!", "Success");
                    }).catch(err => {
                        toastr.error("Failed to copy Webhook URL. Try again.", "Error");
                        console.error("Failed to copy text: ", err);
                    });
                } else {
                    toastr.warning("Please select a campaign first.", "Warning");
                }
            });
        });

        function getAgent(selectObject, agents = [], weightage = []) {


            // Ensure agents and weightage are arrays
            if (!Array.isArray(agents)) {
                agents = Array.from(agents);
            }
            if (!Array.isArray(weightage)) {
                weightage = Array.from(weightage);
                console.log('Weightages:', weightage);
            }

            const selectedOptions = Array.from(selectObject.selectedOptions || []);
            const userFieldsContainer = document.getElementById('user-fields');

            // Create a map for agent weightages based on agentId
            const agentWeightageMap = {};
            agents.forEach((agentId, index) => {
                agentWeightageMap[agentId] = weightage[index] || '';
            });

            // // Remove fields for unselected agents and retain their values for others
            // Array.from(userFieldsContainer.children).forEach(field => {
            //     const fieldId = field.getAttribute('data-user-id');
            //     // Check if the agent is deselected and remove the corresponding field
            //     if (!selectedOptions.find(option => option.value === fieldId)) {
            //         field.remove(); // Remove the field for deselected agents
            //     }
            // });

            // Loop through the selected agents and create/update the input fields
            selectedOptions.forEach(option => {
                const agentId = option.value;
                const agentName = option.text;
                const agentWeightage = agentWeightageMap[agentId] || '';

                // Check if the input field for this agent already exists
                let inputFieldContainer = document.querySelector(`[data-user-id="${agentId}"]`);

                if (!inputFieldContainer) {
                    // If the field doesn't exist, create a new one
                    console.log({
                        'Agent Name': agentName,
                        'Agent Weightage': agentWeightage
                    });

                    const div = document.createElement('div');
                    div.classList.add('col-md-6', 'mb-3');
                    div.setAttribute('data-user-id', agentId);
                    div.innerHTML = `
                <label for="input-${agentId}" class="form-label">Weightage for ${agentName}</label>
                <input type="text" class="form-control" id="input-${agentId}" name="Weightage[${agentId}]"
                       placeholder="Enter weightage for ${agentName}" value="${agentWeightage}" data-weightage-old="${agentWeightage}" id="target-${agentId}">
            `;
                    userFieldsContainer.appendChild(div); // Append the new field
                } else {
                    // If the field already exists, just update the value
                    const inputField = inputFieldContainer.querySelector('input');
                    if (inputField) {
                        inputField.value = agentWeightage;
                    }
                }
            });
        }
    </script>
@endsection
