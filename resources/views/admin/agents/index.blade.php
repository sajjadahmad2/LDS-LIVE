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

        <style>.select2-container--default .select2-selection--multiple {
            border-radius: 6px;
            padding: 5px;
            min-height: 38px;
        }

        .select2-container--default .select2-selection--single {
            border-radius: 6px;
            min-height: 38px;
            padding: 6px 12px;
        }

        .select2-results__options {
            max-height: 200px;
            overflow-y: auto;
        }

        /* Add space between label and input */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        /* Optional: Beautify the loader inside modal */
        .lds-roller.loader {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
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
                    <li class="breadcrumb-item active" aria-current="page">Agents</li>
                </ol>
            </nav>
        </div>
    </div>
    @if (is_role() == 'superadmin' || is_role() == 'admin')
        <!-- End Breadcrumb -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="webhookUrl">Agent Consent URL</label>
                    <div class="input-group">
                        <!-- Display the Route -->
                        <input type="text" class="form-control" id="webhookUrl" value="{{ route('agent.consent') }}"
                            readonly>
                        <!-- Copy Button -->
                        <button class="btn btn-primary" type="button" id="copyWebhookBtn" onclick="copyToClipboard()">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class="col-md-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agentModal"
                    onclick="savaAgentData(0, '', '', '', '', '', '', '','','','',[], [],[],'','','','')">Add Agent</button>
                <button type="button" class="btn btn-primary ml-3" data-bs-toggle="modal" data-bs-target="#agentUser">Agent
                    User</button>
            </div>
            <div class="col-md-3">
                <select class="form-select agent_ids" name="agent" id="agent_ids">

                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select state_ids" name="agent" id="state_ids">

                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select campaign_ids" name="agent" id="campaign_ids">

                </select>
            </div>
            {{-- <div class="col-md-2">

                <input type="text" name="date_range" id="customDateRange" class="form-control customDateRange" />
            </div> --}}
        </div>
        <hr />
    @endif
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="agentsTable" class="table table-striped table-bordered data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>


                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- Use modal-lg for a larger modal -->
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
                    <h5 class="modal-title" id="agentModalLabel">Add/Edit Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="agentForm" method="POST">
                        @csrf
                        <input type="hidden" id="agent_id" name="id">

                        <div class="row">
                            <!-- Name -->
                            <div class="col-md-4 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Enter Name">
                            </div>

                            <!-- Email -->
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="Enter Email">
                            </div>
                            <!-- Destination Location -->

                        </div>

                        <div class="row">


                        </div>
                        <!-- Lead Types as Tabs -->
                        <ul class="nav nav-tabs" id="leadTypeTabs" role="tablist">
                            @foreach ($leadTypes as $index => $leadType)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $leadType->id }}"
                                        data-bs-toggle="tab" data-bs-target="#leadtype-{{ $leadType->id }}"
                                        type="button" role="tab">
                                        {{ $leadType->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content mt-3" id="leadTypeTabsContent">

                            @foreach ($leadTypes as $index => $leadType)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                    id="leadtype-{{ $leadType->id }}" role="tabpanel">

                                    <input type="hidden" name="lead_types[{{ $leadType->id }}][id]"
                                        value="{{ $leadType->id }}">

                                    <div class="row">

                                        <!-- States -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">States</label>
                                            <select name="lead_types[{{ $leadType->id }}][states][]"
                                                class="form-select states-select" multiple>
                                                @foreach ($states as $state)
                                                    <option value="{{ $state->id }}">{{ $state->state }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Carrier Type -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Carrier Type</label>

                                            <select name="lead_types[{{ $leadType->id }}][carrier_type][]"
                                                class="form-select carrier-type-select"
                                                id="carrier_type_{{ $leadType->id }}"
                                                data-lead-type="{{ $leadType->name }}" multiple>

                                                @foreach (getCarrierType($leadType->name) as $type)
                                                    <option value="{{ $type }}">{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="destination_location" class="form-label">Destination
                                                Location</label>
                                            @if (isset($alllocations) && count($alllocations) > 0)
                                                <select name="lead_types[{{ $leadType->id }}][destination_location]"
                                                    class="form-select destination-location-select" style="width: 100%;">
                                                    @foreach ($alllocations as $location)
                                                        <option value="{{ $location->location_id }}">
                                                            {{ $location->location_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" name="destination_location"
                                                    class="form-control destination-location"
                                                    placeholder="Enter Destination Location">
                                            @endif
                                        </div>

                                        <!-- Destination Webhook -->
                                        <div class="col-md-4 mb-3">
                                            <label for="destination_webhook" class="form-label">Destination
                                                Webhook</label>
                                            <input type="text" id="destination_webhook"
                                                name="lead_types[{{ $leadType->id }}][destination_webhook]"
                                                class="form-control" placeholder="Enter Destination Webhook">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Daily Limit</label>
                                            <input type="number" name="lead_types[{{ $leadType->id }}][daily_limit]"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Monthly Limit</label>
                                            <input type="number" name="lead_types[{{ $leadType->id }}][monthly_limit]"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Total Limit</label>
                                            <input type="number" name="lead_types[{{ $leadType->id }}][total_limit]"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">NPN Number</label>
                                            <input type="text" name="lead_types[{{ $leadType->id }}][npm_number]"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Cross Link</label>
                                            <input type="text" name="lead_types[{{ $leadType->id }}][cross_link]"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Consent</label>
                                            <textarea name="lead_types[{{ $leadType->id }}][consent]" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>

                        <div class="row">
                            <div class="col-md-6 ">
                                <div class="form-check ">
                                    <label for="userRoleChecked" class="form-label"></label>
                                    <input class="form-check-input" type="checkbox" id="userRoleChecked"
                                        name="userRoleChecked">
                                    <label class="form-check-label" for="gridCheck">Make this Agent as User</label>
                                </div>
                            </div>

                            <!-- Agent Access Dropdown -->
                            <div class="col-md-6 mb-3" id="agent_access" style="display:none">
                                <label for="agentaccess" class="form-label">User Access</label>
                                @if (isset($alllocations) && count($alllocations) > 0)
                                    <select id="agentaccess" name="agent_access[]" class="form-select agentaccess"
                                        style="width: 100%;" multiple="multiple">
                                        @foreach ($alllocations as $location)
                                            <option value="{{ $location->location_id }}">{{ $location->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <p>No locations available.</p>
                                @endif
                            </div>
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



    <!-- Modal for Agent User -->
    <div class="modal fade" id="agentUser" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- Use modal-lg for a larger modal -->
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
                    <h5 class="modal-title" id="agentModalLabel">Agent User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="agentUser" method="POST">
                        @csrf
                        <div class="row">
                            <!-- Destination Location -->
                            <div class="col-md-12 mb-3">
                                <label for="Agent User" class="form-label"> All Agent</label>
                                <select class="form-control" id="agent_user" name ="agent_user">
                                    <option class="0"> One value </option>
                                </select>
                            </div>
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
@include('admin.select2.select2');
@section('scripts')
    @include('admin.select2.scriptload', ['load' => ['datatable']])
    <script type="text/javascript">
        const allCarrierTypes = @json($carrierTypes);
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            initializeSelect2();
            initializeSelect2ForTabs();

            function initializeSelect2ForTabs() {
                // Static select2 setup
                $('.states-select, .destination-location-select, #agentaccess').select2({
                    tags: true,
                    width: '100%',
                    dropdownParent: $("#agentModal"),
                    allowClear: true,
                });

                $('.carrier-type-select').each(function() {
                    const $select = $(this);
                    const leadType = $select.data('lead-type') || 'Medicare';
                    const allCarriers = allCarrierTypes[leadType] || [];

                    // Build data
                    const carrierObjects = allCarriers.map(entry => ({
                        id: entry,
                        text: entry
                    }));

                    $select.select2({
                        width: '100%',
                        allowClear: true,
                        placeholder: 'Select Carrier Type',
                        dropdownParent: $("#agentModal"),
                        multiple: true,
                        closeOnSelect: false,
                        data: carrierObjects,
                    });

                    $select.on('select2:open', function() {
                        const $dropdown = $('.select2-dropdown');
                        const $search = $('.select2-search__field');

                        // Helper to get visible options from the dropdown DOM
                        function getVisibleOptions() {
                            return $(
                                    '.select2-results__option:not(.select2-results__option--highlight, .select2-select-all-btn)'
                                )
                                .map(function() {
                                    const id = $(this).data('data')?.id || $(this).text();
                                    return id;
                                })
                                .get()
                                .filter(id => id && id !== '');
                        }

                        // Function to render or refresh the button
                        function renderSelectAllButton() {
                            const visibleIds = getVisibleOptions();
                            const count = visibleIds.length;
                            const term = ($search.val() || '').trim();

                            // Remove existing first
                            $dropdown.find('.select2-select-all-btn').remove();

                            if (count > 0) {
                                const label = term ?
                                    `Select ${count} matching "${term}"` :
                                    `Select all (${count}) carriers`;

                                const $btn = $(`
            <li class="select2-results__option select2-select-all-btn"
                role="option"
                style="cursor:pointer; font-weight:600; background:#f8f9fa; border-bottom:1px solid #ddd;">
                ${label}
            </li>
          `);

                                // Inject at top
                                $dropdown.find('.select2-results__options').first().before($btn);

                                // Handle click
                                $btn.off('click').on('click', function(e) {
                                    e.preventDefault();

                                    if (!confirm(
                                            `Select ${count} carriers currently shown?`))
                                        return;

                                    const current = $select.val() || [];
                                    const merged = Array.from(new Set([...current, ...
                                        visibleIds
                                    ]));
                                    $select.val(merged).trigger('change.select2');
                                    $select.select2('close');
                                });
                            }
                        }

                        // Initial render
                        setTimeout(renderSelectAllButton, 100);

                        // Update when typing or dropdown updates
                        $search.off('input.selectAll').on('input.selectAll', function() {
                            setTimeout(renderSelectAllButton, 150);
                        });

                        // Also update when Select2 re-renders results (some versions do that)
                        $(document).off('DOMSubtreeModified.selectAll').on(
                            'DOMSubtreeModified.selectAll', '.select2-results__options',
                            function() {
                                setTimeout(renderSelectAllButton, 150);
                            });

                        // Cleanup
                        $select.one('select2:close', function() {
                            $search.off('input.selectAll');
                            $(document).off('DOMSubtreeModified.selectAll');
                            $dropdown.find('.select2-select-all-btn').remove();
                        });
                    });
                });
            }





            // Loader handling
            $(document).on('select2:opening',
                '.carrier-type-select, .states-select, .destination_location, #agentaccess',
                function() {
                    $('.loader').show();
                }).on('select2:open', function() {
                setTimeout(() => {
                    $('.loader').hide();
                }, 300);
            });
        });
        // re-init when tab changes
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            let target = $(e.target).attr('href'); // e.g. "#tab1"
            initSelect2($(target));
        });
        $(document).ready(function() {

            // Agent Access Hide or Show and clear values when unchecked
            $('#userRoleChecked').change(function() {
                if ($(this).is(":checked")) {
                    $('#agent_access').fadeIn(); // Show the #agent_access div
                    $('#agentaccess').prop('required', true); // Make the select field required
                } else {
                    $('#agent_access').fadeOut(); // Hide the #agent_access div
                    $('#agentaccess').val(null).trigger('change'); // Clear the select values
                    $('#agentaccess').prop('required', false); // Remove the required attribute
                }
            });
            var table = $('#agentsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    "url": "{{ route('admin.agents.index') }}",
                    data: function(d) {
                        d.agent_ids = $('.agent_ids').val() || null;
                        d.state_ids = $('.state_ids').val() || null;
                        d.campaign_ids = $('.campaign_ids').val() || null;
                        d.campaign_ids = $('.campaign_ids').val() || null;
                        d.customDateRange = $('.customDateRange').val() || null;
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    // {
                    //     data: 'states',
                    //     name: 'states',
                    //     render: function(data, type, row) {
                    //         return `<div class="states-column">${data}</div>`;
                    //     }
                    // },


                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });
            $('body').on('change', '.agent_ids,.state_ids, .campaign_ids, .customDateRange', function() {
                table.ajax.reload();
            });
            // Submit Form
            $('#agentForm').submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                let submitButton = $('#submitButton');
                submitButton.prop('disabled', true).html('Loading...');
                let agentId = $('#agent_id').val();
                let method = agentId && agentId != "0" ? 'PUT' : 'POST';
                let url = agentId && agentId != "0" ?
                    "{{ route('admin.agents.update', ':id') }}".replace(':id', agentId) :
                    "{{ route('admin.agents.store') }}";

                $.ajax({
                    type: method,
                    url: url,
                    data: formData,
                    success: function(response) {
                        submitButton.prop('disabled', false).html('Save');
                        $('#agentModal').modal('hide');
                        toastr.success(response.message);
                        $('#agentForm')[0].reset();
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        submitButton.prop('disabled', false).html('Save');

                        // If validation errors are returned, show them
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            // Remove previous error messages
                            $('.text-danger').remove();
                            $('.is-invalid').removeClass('is-invalid');

                            // Loop through each error and append it
                            $.each(errors, function(key, value) {
                                let input = $('[name="' + key + '"]');
                                input.addClass(
                                    'is-invalid'); // Add Bootstrap invalid class
                                input.after('<span class="text-danger">' + value[0] +
                                    '</span>'); // Show error message
                            });

                            toastr.error('Validation failed. Please check the errors.');
                        } else {
                            // If it's not a validation error, show a generic error message
                            toastr.error(xhr.responseJSON.message || 'Something went wrong.');
                        }
                    }
                });
            });

            // This ensures that when the modal is opened, previous error messages are displayed
            $('#agentModal').on('show.bs.modal', function() {
                // Remove previous error messages each time the modal is opened
                $('.text-danger').remove();
                $('.is-invalid').removeClass('is-invalid');
            });
            // Populate Form for Edit
            window.savaAgentData = function(
                id,
                name,
                email,
                destination_location, // array of { destination_location, lead_type_id }
                destination_webhook, // array of { destination_webhook, lead_type_id }
                dailyLimits, // array of { daily_limit, lead_type_id }
                monthlyLimits, // array of { monthly_limit, lead_type_id }
                totalLimits, // array of { total_limit, lead_type_id }
                consents, // array of { consent, lead_type_id }
                carrierTypes, // array of { carrier_type, lead_type_id }
                states, // array of { state_id, lead_type_id }
                npmNumbers, // array of { npm_number, lead_type_id }
                crossLinks, // array of { cross_link, lead_type_id }
                agentUsers, // array of locationIds
                userRole // boolean
            ) {
                // ===== Basic agent header fields =====
                $('#agent_id').val(id);
                $('#name').val(name);
                $('#email').val(email);

                // ===== Role / access =====
                if (userRole === true) {
                    $('#userRoleChecked').prop('checked', true);
                    $('#agent_access').fadeIn();
                    $('#agentaccess').val(agentUsers || []).trigger('change');
                    $('#agentaccess').prop('required', true);
                } else {
                    $('#userRoleChecked').prop('checked', false);
                    $('#agent_access').fadeOut();
                    $('#agentaccess').val([]).trigger('change');
                    $('#agentaccess').prop('required', false);
                }

                // ===== Group incoming states & carriers by lead_type_id =====
                const
                grouped = {}; // { [leadTypeId]: { states: [], carriers: [], limits: {}, consent: '', npm_number:'', cross_link:'', destination_location:'', destination_webhook:'' } }

                function ensure(key) {
                    if (!grouped[key]) grouped[key] = {
                        states: [],
                        carriers: [],
                        daily_limit: '',
                        monthly_limit: '',
                        total_limit: '',
                        consent: '',
                        npm_number: '',
                        cross_link: '',
                        destination_location: '',
                        destination_webhook: ''
                    };
                    return grouped[key];
                }

                (Array.isArray(states) ? states : []).forEach(s => {
                    const key = s && s.lead_type_id != null ? s.lead_type_id : 'all';
                    ensure(key).states.push(String(s.state_id));
                });

                (Array.isArray(carrierTypes) ? carrierTypes : []).forEach(c => {
                    const key = c && c.lead_type_id != null ? c.lead_type_id : 'all';
                    ensure(key).carriers.push(String(c.carrier_type));
                });

                (Array.isArray(dailyLimits) ? dailyLimits : []).forEach(d => {
                    const key = d.lead_type_id != null ? d.lead_type_id : 'all';
                    ensure(key).daily_limit = d.daily_limit || '';
                });

                (Array.isArray(monthlyLimits) ? monthlyLimits : []).forEach(m => {
                    const key = m.lead_type_id != null ? m.lead_type_id : 'all';
                    ensure(key).monthly_limit = m.monthly_limit || '';
                });

                (Array.isArray(totalLimits) ? totalLimits : []).forEach(t => {
                    const key = t.lead_type_id != null ? t.lead_type_id : 'all';
                    ensure(key).total_limit = t.total_limit || '';
                });

                (Array.isArray(consents) ? consents : []).forEach(c => {
                    const key = c.lead_type_id != null ? c.lead_type_id : 'all';
                    ensure(key).consent = c.consent || '';
                });

                (Array.isArray(npmNumbers) ? npmNumbers : []).forEach(n => {
                    const key = n.lead_type_id != null ? n.lead_type_id : 'all';
                    ensure(key).npm_number = n.npm_number || '';
                });

                (Array.isArray(crossLinks) ? crossLinks : []).forEach(c => {
                    const key = c.lead_type_id != null ? c.lead_type_id : 'all';
                    ensure(key).cross_link = c.cross_link || '';
                });

                (Array.isArray(destination_location) ? destination_location : []).forEach(d => {
                    const key = d.lead_type_id != null ? d.lead_type_id : 'all';
                    ensure(key).destination_location = d.destination_location || '';
                });

                (Array.isArray(destination_webhook) ? destination_webhook : []).forEach(d => {
                    const key = d.lead_type_id != null ? d.lead_type_id : 'all';
                    ensure(key).destination_webhook = d.destination_webhook || '';
                });

                // ===== Apply values to each lead-type tab =====
                $('[id^="leadtype-"]').each(function() {
                    const tabId = $(this).attr('id'); // e.g., leadtype-3
                    const leadTypeId = tabId.split('-')[1];
                    const group = grouped[leadTypeId] || grouped['all'] || {};

                    // States
                    $(this).find(`[name="lead_types[${leadTypeId}][states][]"]`).val(group.states || [])
                        .trigger('change');

                    // Carrier Types
                    $(this).find(`[name="lead_types[${leadTypeId}][carrier_type][]"]`).val(group
                        .carriers || []).trigger('change');

                    // Limits
                    $(this).find(`[name="lead_types[${leadTypeId}][daily_limit]"]`).val(group
                        .daily_limit || '');
                    $(this).find(`[name="lead_types[${leadTypeId}][monthly_limit]"]`).val(group
                        .monthly_limit || '');
                    $(this).find(`[name="lead_types[${leadTypeId}][total_limit]"]`).val(group
                        .total_limit || '');

                    // Consent & Other Fields
                    $(this).find(`[name="lead_types[${leadTypeId}][consent]"]`).val(group.consent ||
                    '');
                    $(this).find(`[name="lead_types[${leadTypeId}][npm_number]"]`).val(group
                        .npm_number || '');
                    $(this).find(`[name="lead_types[${leadTypeId}][cross_link]"]`).val(group
                        .cross_link || '');
                    $(this).find(`[name="lead_types[${leadTypeId}][destination_webhook]"]`).val(group
                        .destination_webhook || '');

                    // ===== Destination Location =====
                    const $dest = $(this).find(
                        `[name="lead_types[${leadTypeId}][destination_location]"]`);
                    if (group.destination_location) {
                        // If option doesn't exist, create it
                        if ($dest.find(`option[value="${group.destination_location}"]`).length === 0) {
                            const newOption = new Option(group.destination_location, group
                                .destination_location, true, true);
                            $dest.append(newOption);
                        }
                        $dest.val(group.destination_location).trigger('change');
                    }
                });
            };



            window.deleteAgent = function(agentId) {
                if (confirm("Are you sure you want to delete this agent?")) {
                    $.ajax({
                        url: "{{ route('admin.agents.destroy', ':id') }}".replace(':id', agentId),
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            console.error(xhr.responseJSON.message);
                            console.error(xhr.responseJSON.line);
                        },
                    });
                }
            };

            $('body').on('click', '.status_changes', function(e) {
                e.preventDefault();
                const id = $(this).data('status');
                const url = "{{ route('admin.agent.status', ':id') }}".replace(':id', id);
                if (confirm("Are you sure you want to change the status of this user?")) {
                    $.ajax({
                        type: "GET",
                        url: url,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message ||
                                    'User status changed successfully!');
                                if (typeof table !== 'undefined') {
                                    table.ajax.reload();
                                } else {
                                    location.reload();
                                }
                            } else {
                                toastr.error(response.message ||
                                    'Failed to change user status.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error:", error);
                            toastr.error('An error occurred while updating the status.');
                        }
                    });
                }
            });
            $("form#agentUser").submit(function(event) {
                event.preventDefault(); // Prevent default form submission

                let formData = $(this).serialize();
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.agent.user.save') }}",
                    data: formData,
                    success: function(response) {
                        // Reset form and hide modal
                        $("form#agentUser")[0].reset();
                        $('#agentUser').modal('hide');
                        toastr.success(response.message || 'User saved successfully!');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            if (errors.email) {
                                toastr.error(errors.email[0]);
                            }
                        } else {
                            toastr.error("There was an error processing the request.");
                        }
                    }
                });
            });
        });

        function copyToClipboard() {
            // Get the input element
            const webhookInput = document.getElementById("webhookUrl");
            webhookInput.select();
            webhookInput.setSelectionRange(0, 99999); // For mobile devices

            // Copy the text inside the input
            navigator.clipboard.writeText(webhookInput.value).then(() => {
                alert("URL copied to clipboard: " + webhookInput.value);
            }).catch((err) => {
                console.error("Failed to copy text: ", err);
            });
        }
        loadAgentUsers();
        $('#agentUser').on('show.bs.modal', function() {
            loadAgentUsers();
        });

        function loadAgentUsers() {
            $.ajax({
                url: "{{ route('admin.agent.user') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        let agents = response.data;
                        console.log(agents);
                        let options = '<option value="">Select Agent</option>';
                        $.each(agents, function(index, agent) {
                            options += `<option value="${agent.id}">${agent.name}</option>`;
                        });
                        $("#agent_user").html(options);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    </script>
@endsection
