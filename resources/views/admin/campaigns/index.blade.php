@extends('admin.layouts.index')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .agent-row td:last-child {
  text-align: center;
  vertical-align: middle;
}

    </style>
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
    <!-- Campaign Modal -->
    <div class="modal fade" id="campaignModal" tabindex="-1" aria-labelledby="campaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-white border-bottom-0 px-4 py-3">
                    <h5 class="modal-title fw-semibold" id="campaignModalLabel">Add / Edit Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="campaignForm" method="POST">
                    @csrf
                    <input type="hidden" id="campaign_id" name="id" />

                    <div class="modal-body px-4">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Campaign Name</label>
                            <input type="text" id="campaign_name" name="campaign_name" class="form-control rounded-3"
                                placeholder="Enter Campaign Name" required>
                        </div>

                        <div class="mb-3 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="addAgentBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Agent
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Agent <span class="text-danger">*</span></th>
                                        <th>Priority</th>
                                        <th>Weightage</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="agent-fields"></tbody>
                            </table>
                        </div>

                        {{-- <div id="agent-fields" class="row gy-3"></div> --}}
                    </div>

                    <div class="modal-footer bg-white border-top-0 px-4 py-3">
                        <button type="button" class="btn btn-light border rounded-pill"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="submitButton">Save
                            Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        const agentsData = @json($agents);

        $(document).ready(function() {
            const agentContainer = $('#agent-fields');
            const campaignForm = $('#campaignForm');
            const submitButton = $('#submitButton');
            const campaignDropdown = $('#campaignDropdown');
            const webhookUrlInput = $('#webhookUrl');
            const copyWebhookBtn = $('#copyWebhookBtn');

            // ===========================
            // Agent Management Functions
            // ===========================

            function refreshAgentOptions() {
                const selected = agentContainer.find('.agent-select').map(function() {
                    return $(this).val();
                }).get();

                agentContainer.find('.agent-select').each(function() {
                    const currentSelect = $(this);
                    const currentVal = currentSelect.val();

                    currentSelect.empty();
                    agentsData.forEach(agent => {
                        if (!selected.includes(String(agent.id)) || String(agent.id) ===
                            currentVal) {
                            currentSelect.append(
                                `<option value="${agent.id}">${agent.name}</option>`);
                        }
                    });
                    currentSelect.val(currentVal);
                });
            }

            function appendAgentRow(agentId = "", priority = "", weightage = "") {
                const rowId = Date.now() + Math.floor(Math.random() * 1000);

                const agentOptions = [
                    `<option value="">Choose agent</option>`,
                    ...agentsData.map(a =>
                        `<option value="${a.id}" ${a.id == agentId ? 'selected' : ''}>${a.name}</option>`
                    )
                ].join('');

                const row = $(`
                    <tr class="agent-row" data-id="${rowId}">
                        <td>
                        <select name="agents[${rowId}]" class="form-select form-select-sm rounded-2 agent-select" required>
                            ${agentOptions}
                        </select>
                        </td>
                        <td>
                        <input type="number" name="priority[${rowId}]" class="form-control form-control-sm rounded-2" value="${priority}" placeholder="e.g. 1" />
                        </td>
                        <td>
                        <input type="number" name="weightage[${rowId}]" class="form-control form-control-sm rounded-2" value="${weightage}" placeholder="e.g. 50" />
                        </td>
                        <td class="text-end">
                        <a href="javascript:void(0);" class="text-danger fs-5 remove-agent" title="Remove">
                        <i class="bi bi-trash"></i>
                        </a>

                        </td>
                    </tr>
                    `);

                $('#agent-fields').prepend(row);
                refreshAgentOptions();
            }
            // Add Agent Button
            $('#addAgentBtn').on('click', function() {
                appendAgentRow();
            });

            // Remove Agent Row
            agentContainer.on('click', '.remove-agent', function() {
                $(this).closest('.agent-row').remove();
                refreshAgentOptions();
            });

            // Prevent duplicate agent selection
            agentContainer.on('change', '.agent-select', function() {
                refreshAgentOptions();
            });

            // ===========================
            // Campaign Form Submission
            // ===========================

            campaignForm.submit(function(e) {
                e.preventDefault();
                let isValid = true;

                // Validate campaign name
                if (!$('#campaign_name').val().trim()) {
                    toastr.error("Campaign name is required.");
                    $('#campaign_name').focus();
                    return;
                }

                // Validate agent selects
                $('.agent-select').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    toastr.error("All agent rows must have a selected agent.");
                    return;
                }
                let formData = campaignForm.serialize();
                let campaignId = $('#campaign_id').val();
                let method = campaignId && campaignId != "0" ? 'PUT' : 'POST';
                let url = campaignId && campaignId != "0" ?
                    "{{ route('admin.campaigns.update', ':id') }}".replace(':id', campaignId) :
                    "{{ route('admin.campaigns.store') }}";

                submitButton.prop('disabled', true).html('Loading...');

                $.ajax({
                    type: method,
                    url: url,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            submitButton.prop('disabled', false).html('Save');
                            $('#campaignModal').modal('hide');
                            campaignForm[0].reset();
                            agentContainer.empty();
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

            // ===========================
            // Edit Campaign Handler
            // ===========================
            window.savaCampaignData = function(id, campaign_name, agents, priority, weightage) {
                const isNew = !id || id == 0;

                $('#campaign_id').val(id);
                $('#campaign_name').val(campaign_name || '');

                const agentContainer = $('#agent-fields');
                agentContainer.empty(); // Clear all agent rows

                if (!isNew) {
                    const agentArray = Array.isArray(agents) ? agents : JSON.parse(agents);
                    const priorityArray = Array.isArray(priority) ? priority : JSON.parse(priority);
                    const weightageArray = Array.isArray(weightage) ? weightage : JSON.parse(weightage);

                    agentArray.forEach((agentId, index) => {
                        const agentPriority = priorityArray[index] || "";
                        const agentWeightage = weightageArray[index] || "";
                        appendAgentRow(agentId, agentPriority, agentWeightage);
                    });
                }

                // Reset any validation errors or leftovers
                $('.agent-select').removeClass('is-invalid');
                $('#campaign_name').removeClass('is-invalid');

                $('#campaignModal').modal('show');
            };


            // ===========================
            // DataTable + Webhook Handling
            // ===========================

            const table = $('#campaignsTable').DataTable({
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
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            function loadCampaigns() {
                $.ajax({
                    url: '/admin/campaign/show',
                    method: 'GET',
                    success: function(response) {
                        let campaignDropdown = $('.campaignDropdown');
                        campaignDropdown.empty().append(
                            '<option value="">-- Select a Campaign --</option>');
                        $.each(response, function(index, campaign) {
                            campaignDropdown.append('<option value="' + campaign.id + '">' +
                                campaign.campaign_name + '</option>');
                        });
                        campaignDropdown.trigger('change');
                    },
                    error: function() {
                        toastr.error('Failed to load campaigns.');
                    }
                });
            }


            // ===========================
            // Copy  Campaign URL
            // ===========================


            window.copyCampaignUrl = function(campaignId) {
                const encodedId = btoa(campaignId).slice(0, 40);
                const baseApiRoute = "{{ route('api.webhook.lead', ['campaign_id' => ':campaign_id']) }}";
                const webhookUrl = baseApiRoute.replace(':campaign_id', encodedId);

                navigator.clipboard.writeText(webhookUrl).then(() => {
                    toastr.success("Webhook URL copied to clipboard!", "Success");
                }).catch(err => {
                    toastr.error("Failed to copy Webhook URL. Try again.", "Error");
                    console.error("Clipboard error:", err);
                });
            }

            // ===========================
            // Delete Campaign
            // ===========================

            window.deleteCampaign = function(campaignId) {
                if (confirm("Are you sure you want to delete this campaign?")) {
                    $.ajax({
                        url: "{{ route('admin.campaigns.destroy', ':id') }}".replace(':id', campaignId),
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function() {
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseJSON.message);
                        }
                    });
                }
            };

            // Init
            loadCampaigns();
        });
    </script>
@endsection
