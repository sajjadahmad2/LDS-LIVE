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
    @if (is_role() == 'superadmin' || is_role() == 'admin')
        <button type="button" class="btn btn-primary me-2 mb-3" data-bs-toggle="modal" data-bs-target="#campaignModal"
            onclick="savaCampaignData(0, '',[],'','')">Add Campaign</button>
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#copyWebhookModal">
            Copy Webhook URL
        </button>
        <hr />
    @endif
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="campaignsTable" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign Name</th>
                            <th>Agents</th>
                            <th>Priority</th>
                            <th>Weightage</th>
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
                        <select id="campaignDropdown" class="form-select campaignDropdown">
                            <option value="">-- Select a Campaign --</option>
                            <!-- Options will be populated via AJAX -->
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
                                    placeholder="Enter Campaign Name">
                            </div>
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

                        <div class="row">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success" id="submitButton">Save</button>
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
            loadCampaigns();
            $('#agents').select2({
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
                        data: 'agents',
                        name: 'agents'
                    },

                    {
                        data: 'priority',
                        name: 'priority'
                    },

                    {
                        data: 'weightage',
                        name: 'weightage'
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
                let submitButton = $('#submitButton');
                submitButton.prop('disabled', true).html('Loading...');
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
                        console.log(response);
                        if (response.success) {
                            submitButton.prop('disabled', false).html('Save');
                            $('#campaignModal').modal('hide');
                            $('#campaignForm')[0].reset();
                            $('#agents').val(null).trigger('change');
                            $('#user-fields').empty();
                            table.ajax.reload();
                            loadCampaigns();
                        } else {
                            alert('Failed to save campaign: ' + response.message);
                            submitButton.prop('disabled', false).html('Save');
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseJSON.message);
                        submitButton.prop('disabled', false).html('Save');
                    }
                });
            });

            window.savaCampaignData = function(id, campaign_name, agents, priority, weightage) {
           //console.log(id, campaign_name, agents, priority, weightage);

    // Set the campaign ID and campaign name
    $('#campaign_id').val(id);
    $('#campaign_name').val(campaign_name);
    // Parse the arrays if they are passed as strings
    const agentArray = Array.isArray(agents) ? agents : JSON.parse(agents);
    const priorityArray = Array.isArray(priority) ? priority : JSON.parse(priority);
    const weightageArray = Array.isArray(weightage) ? weightage : JSON.parse(weightage);
    // Set the agents in the select field
    if (agentArray.length) {
        $('#agents').val(agentArray).trigger('change'); // Select agents
    } else {
        $('#agents').val([]).trigger('change'); // Clear selection
    }

    // Clear existing fields in the modal before appending new ones
    $('#user-fields').empty();

    // Append fields for each agent (priority and weightage)
    agentArray.forEach((agentId, index) => {
        const agentPriority = priorityArray[index] || "";
        const agentWeightage = weightageArray[index] || "";

        const agentName = $('#agents option[value="' + agentId + '"]').text();

        // Call appendAgentField to insert the fields dynamically
        appendAgentField(document.getElementById('user-fields'), agentId, agentName, agentPriority, agentWeightage);
    });

    // Show the modal
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

            const campaignDropdown = $('#campaignDropdown');
            const webhookUrlInput = $('#webhookUrl');
            const copyWebhookBtn = $('#copyWebhookBtn');

            const userId = "{{ auth()->user()->location_id ?? auth()->user()->id }}";
            const baseApiRoute =
                "{{ route('api.webhook.lead', ['campaign_id' => ':campaign_id']) }}";

            // Handle Campaign Selection
            campaignDropdown.on('change', function() {
                const campaignId = $(this).val();
                const encodedid = btoa(campaignId).slice(0, 40)
                if (campaignId) {
                    const webhookUrl = baseApiRoute
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

            // Function to load campaigns into dropdowns
            function loadCampaigns() {
                $.ajax({
                    url: '/admin/campaign/show',
                    method: 'GET',
                    success: function(response) {
                        let campaignDropdown = $('.campaignDropdown');
                        campaignDropdown.empty();
                        campaignDropdown.append('<option value="">-- Select a Campaign --</option>');

                        $.each(response, function(index, campaign) {
                            campaignDropdown.append('<option value="' + campaign.id + '">' +
                                campaign.campaign_name + '</option>');
                        });

                        // Refresh the Select2 control
                        campaignDropdown.trigger('change');
                    },
                    error: function(xhr) {
                        toastr.error('Failed to load campaigns.');
                    }
                });
            }

        });
        function getAgent(selectObject) {
    const campaignId = $('#campaign_id').val();
    const agentIds = Array.from(selectObject.selectedOptions).map(option => option.value);
    const agentNames = Array.from(selectObject.selectedOptions).map(option => option.text);
    const userFieldsContainer = document.getElementById('user-fields');

    // Loop through each selected agent and add the fields
    agentIds.forEach((agentId, index) => {
        const agentName = agentNames[index];
        appendAgentField(userFieldsContainer, agentId, agentName);
    });

    // Remove fields for unselected agents
    const selectedAgentIds = new Set(agentIds);
    const allAgentFields = userFieldsContainer.querySelectorAll('.row');
    allAgentFields.forEach(row => {
        const agentId = row.getAttribute('data-user-id');
        if (!selectedAgentIds.has(agentId)) {
            userFieldsContainer.removeChild(row);  // Remove the field for this agent
        }
    });
}

function appendAgentField(container, agentId, agentName, priority = "", weightage = "") {
    let existingField = container.querySelector(`[data-user-id="${agentId}"]`);

    if (!existingField) {
        const row = document.createElement('div');
        row.classList.add('row', 'mb-3');
        row.setAttribute('data-user-id', agentId);
        row.innerHTML = `
            <div class="col-md-6">
                <label for="weightage-${agentId}" class="form-label">Weightage for ${agentName}</label>
                <input type="number" id="weightage-${agentId}" name="Weightage[${agentId}]"
                    class="form-control" placeholder="Enter weightage for ${agentName}" value="${weightage}">
            </div>
            <div class="col-md-6">
                <label for="priority-${agentId}" class="form-label">Priority for ${agentName}</label>
                <select class="form-select" id="priority-${agentId}" name="priority[${agentId}]">
                    <option selected disabled value="">Please Enter Priority...</option>
                    ${Array.from({ length: 10 }, (_, i) => `<option value="${i + 1}" ${priority === (i + 1) ? 'selected' : ''}>${i + 1}</option>`).join('')}
                </select>
            </div>
        `;
        container.appendChild(row);
    }
}

    </script>
@endsection
