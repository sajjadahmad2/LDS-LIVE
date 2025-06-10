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
                            <th>States</th>
                            <th>Weightage</th>
                            {{-- <th>Weightage Count</th> --}}
                            <th>Priority</th>
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

                    <form id="agentForm" method="POST">
                        @csrf
                        <input type="hidden" id="agent_id" name="id">

                        <div class="row">
                            <!-- Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Enter Name">
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="Enter Email">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Destination Location -->
                            <div class="col-md-6 mb-3">
                                <label for="destination_location" class="form-label">Destination Location</label>
                                @if (isset($alllocations) && count($alllocations) > 0)
                                    <select id="destination_location" name="destination_location" class="form-select"
                                        style="width: 100%;">
                                        @foreach ($alllocations as $location)
                                            <option value="{{ $location->location_id }}">{{ $location->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" id="destination_location" name="destination_location"
                                        class="form-control" placeholder="Enter Destination Location">
                                @endif
                            </div>

                            <!-- Destination Webhook -->
                            <div class="col-md-6 mb-3">
                                <label for="destination_webhook" class="form-label">Destination Webhook</label>
                                <input type="text" id="destination_webhook" name="destination_webhook"
                                    class="form-control" placeholder="Enter Destination Webhook">
                            </div>
                        </div>

                        <div class="row">
                            <!-- States -->
                            <div class="col-md-6 mb-3">
                                <label for="states" class="form-label">States</label>
                                <select id="states" name="states[]" class="form-select" multiple="multiple"
                                    style="width: 100%;">
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->state }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Carrier Type -->
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
                            <!-- Priority -->
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

                            <!-- Daily Limit -->
                            <div class="col-md-6 mb-3">
                                <label for="weightage" class="form-label">Weightage</label>
                                <input type="number" id="weightage" name="weightage" class="form-control"
                                    placeholder="Enter Weightage">

                            </div>
                        </div>
                        <div class="row">


                            <div class="col-md-4 mb-3">
                                <label for="daily_limit" class="form-label">Daily Limit</label>
                                <input type="number" id="daily_limit" name="daily_limit" class="form-control"
                                    placeholder="Enter Daily Limit">

                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="monthly_limit" class="form-label">Monthly Limit</label>
                                <input type="number" id="monthly_limit" name="monthly_limit" class="form-control"
                                    placeholder="Enter Monthly Limit">
                            </div>
                            <!-- Total  Limit -->
                            <div class="col-md-4 mb-3">
                                <label for="total_limit" class="form-label">Total Limit</label>
                                <input type="number" id="total_limit" name="total_limit" class="form-control"
                                    placeholder="Enter Total Limit">
                            </div>
                        </div>

                        <div class="row">
                            <!-- npm number-->
                            <div class="col-md-6 mb-3">
                                <label for="npm_number" class="form-label">National Producer Number (NPN)</label>
                                <input type="text" id="npm_number" name="npm_number" class="form-control"
                                    placeholder="Enter National Producer Number">
                            </div>
                            <!-- cross_link  -->
                            <div class="col-md-6 mb-3">
                                <label for="total_limit" class="form-label">Cross Link</label>
                                <input type="text" id="cross_link" name="cross_link" class="form-control"
                                    placeholder="Enter Cross Link">
                            </div>
                        </div>
                        <div class="row">
                            <!-- Consent -->
                            <div class="col-md-6 mb-3">
                                <label for="consent" class="form-label">Consent</label>
                                <textarea id="consent" name="consent" class="form-control" rows="3" placeholder="Enter Consent Details"></textarea>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="form-check mt-3">
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
            $(document).ready(function() {
            // initializeSelect2();
            // let $select = $('#carrier_type');
            // $select.select2({
            //     tags: true,
            //     width: '100%',
            //     allowClear: true,
            //     dropdownParent: $("#agentModal"),
            });

            function makeSortable() {
                let $selection = $('.select2-selection__rendered');
                $selection.sortable({
                    containment: "parent",
                    items: ".select2-selection__choice",
                    stop: function() {
                        let sortedValues = [];
                        $selection.children('.select2-selection__choice').each(function() {
                            let text = $(this).text().trim();
                            let value = $select.find("option").filter(function() {
                                return $(this).text().trim() === text;
                            }).val();
                            if (value) {
                                sortedValues.push(value);
                            }
                        });

                        // Prevent empty selection issue
                        if (sortedValues.length > 0) {
                            $select.val(sortedValues).trigger('change.select2');
                        }
                    }
                }).addClass('sortable-selection');
            }

            // Apply sorting after Select2 is opened
            $select.on('select2:open', function() {
                setTimeout(makeSortable, 100);
            });

            // Keep sorting enabled after selection changes
            $select.on('select2:select select2:unselect', function() {
                setTimeout(makeSortable, 100);
            });

            // Ensure sorting works on page load
            setTimeout(makeSortable, 500);
        });
        $(document).ready(function() {
            // Initialize Select2
            $('#carrier_type, #states, #destination_location, #agentaccess').select2({
                tags: true,
                width: '100%',
                dropdownParent: $("#agentModal"),
                allowClear: true,
            });
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
                    {
                        data: 'states',
                        name: 'states',
                        render: function(data, type, row) {
                            return `<div class="states-column">${data}</div>`;
                        }
                    },
                    {
                        data: 'weightage',
                        name: 'weightage'
                    },
                    // {
                    //     data: 'agent_count_weightage',
                    //     name: 'agent_count_weightage'
                    // },
                    {
                        data: 'priority',
                        name: 'priority'
                    },
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

            // Populate Form for Edit
            window.savaAgentData = function(id, name, email, destination_location, destination_webhook, priority,
                daily_limit, monthly_limit, total_limit, consent, carrier_type, states, agentUser, npm_number,
                weightage, cross_link, userRole) {
                $('#agent_id').val(id);
                $('#name').val(name);
                $('#email').val(email);
                $('#destination_location').val(destination_location);
                $('#destination_webhook').val(destination_webhook);
                $('#priority').val(priority);
                $('#daily_limit').val(daily_limit);
                $('#monthly_limit').val(monthly_limit);
                $('#total_limit').val(total_limit);
                let formattedConsent = consent.replace(/\\r\\n/g, '\n').replace(/\\n/g, '\n').replace(/"/g,
                ' ');
                console.log(formattedConsent);
                $('#consent').val(formattedConsent);
                // Populate the consent field
                $('#npm_number').val(npm_number);
                $('#weightage').val(weightage);
                $('#cross_link').val(cross_link);
                // Populate Carrier Type
                if (carrier_type && carrier_type.length) {
                    $('#carrier_type').val(carrier_type).trigger('change');
                } else {
                    $('#carrier_type').val([]).trigger('change');
                }
                if (destination_location && destination_location.length) {
                    $('#destination_location').val(destination_location).trigger('change');
                } else {
                    $('#destination_location').val([]).trigger('change');
                }

                // Populate States
                if (states && states.length) {
                    $('#states').val(states).trigger('change');
                } else {
                    $('#states').val([]).trigger('change');
                }

                if (userRole === true) {
                    $('#userRoleChecked').prop('checked', true);
                    $('#agent_access').fadeIn();
                    $('#agentaccess').prop('required', true);
                } else {
                    $('#userRoleChecked').prop('checked', false);
                    $('#agent_access').fadeOut();
                    $('#agentaccess').val(null).trigger('change');
                    $('#agentaccess').prop('required', false);
                }

                if (agentUser && agentUser.length) {
                    $('#agentaccess').val(agentUser).trigger('change');
                } else {
                    $('#agentaccess').val([]).trigger('change');
                }
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
                        },
                    });
                }
            };
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

        $(document).ready(function() {
    let $select = $('#carrier_type');
    $select.select2({
        tags: true,
        width: '100%',
        allowClear: true,
        dropdownParent: $("#agentModal").length ? $("#agentModal") : $(document.body),
    });
    function updateDOMOrder(sortedValues) {
        // Create new option list in sorted order for selected
        let newOptionsHtml = '';
        sortedValues.forEach(function(val) {
            let $option = $select.find(`option[value="${val}"]`);
            if ($option.length > 0) {
                newOptionsHtml += `<option value="${val}" selected>${$option.text()}</option>`;
            } else {
                // In case it's a new tag
                newOptionsHtml += `<option value="${val}" selected>${val}</option>`;
            }
        });
        // Add unselected options back (to preserve all options)
        $select.find('option:not(:selected)').each(function() {
            newOptionsHtml += `<option value="${$(this).val()}">${$(this).text()}</option>`;
        });
        $select.html(newOptionsHtml);  // Replace options
        $select.val(sortedValues).trigger('change.select2');  // Refresh selection
    }
    function makeSortable() {
        let $selection = $select.next('.select2-container').find('.select2-selection__rendered');
        if ($selection.hasClass('ui-sortable')) {
            $selection.sortable('destroy');
        }
        $selection.sortable({
            containment: "parent",
            items: ".select2-selection__choice",
            tolerance: 'pointer',
            stop: function() {
                let sortedValues = [];
                $selection.children('.select2-selection__choice').each(function() {
                    let text = $(this).attr('title').trim();
                    let value = $select.find("option").filter(function() {
                        return $(this).text().trim() === text;
                    }).val() || text; // Fallback for new tag
                    sortedValues.push(value);
                });
                if (sortedValues.length > 0) {
                    updateDOMOrder(sortedValues);
                }
            }
        });
    }
    // Trigger sortable on open/select/unselect
    $select.on('select2:open select2:select select2:unselect', function() {
        setTimeout(makeSortable, 10);
    });
    // Initial sortable setup
    setTimeout(makeSortable, 100);
});
    </script>
@endsection
