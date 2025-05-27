@extends('admin.layouts.index')
@section('content')
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Setting</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">CRM</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->
        <div class="row">
            @if(is_role()=='superadmin')
            <div class="card">
                <div class="card-body">
                    <form id="submitForm" method="POST">
                        @csrf
                        <div class="col-md-12">
                            <div class="card-header">
                                <h4 class="h4">CRM OAuth Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="clientID" class="form-label"> Client ID</label>
                                            <input type="text" class="form-control "
                                                value="{{ $settings['crm_client_id'] ?? '' }}" id="crm_client_id"
                                                name="setting[crm_client_id]" aria-describedby="clientID" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="clientID" class="form-label"> Client secret</label>
                                        <input type="text" class="form-control "
                                            value="{{ $settings['crm_client_secret'] ?? '' }}" id="crm_secret_id"
                                            name="setting[crm_client_secret]" aria-describedby="secretID" required>
                                    </div>
                                    <div class="col-md-12 m-2">
                                        <button id="form_submit" class="btn btn-primary">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <div class="col-lg-6 col-md-6 ml-auto">
                <div class="card card-default">
                    <div class="card-header"><span class="header-title">CRM Connection</span></div>
                    <div class="card-body">

                        {{-- @php
                            $user = auth()->user()->load('ghlauth');

                            $company = $user ? $user->ghlauth[0]->user_id : null; // Assuming the user has a company attribute
                        @endphp --}}

                        {{-- @if($user && $company) --}}
                            {{-- <p class="">CRM is already connected to your company.</p> --}}
                        {{-- @else --}}
                            <a href="{{ CRM::directConnect() }}" class="connect_crm btn btn-success text-center my-3">
                                Connect To @if(is_role()=='superadmin') Agency/CRM @else Location/CRM @endif ({{ is_role() }})
                            </a>
                        {{-- @endif --}}
                    </div>
                </div>
            </div><br/>


            @php
            $logo = App\Models\Setting::where('key', 'logo')->first();
            @endphp
            <div class="col-lg-12 col-md-12 ml-auto">
                <div class="card card-default">
                    <div class="card-header"><span class="header-title">Logo</span></div>
                    <div class="card-body">
                        <form action="{{ route('setting.saveLogo') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p>
                                <input type="file" accept="image/*" name="image" id="file" onchange="loadFile(event)" style="display: none;">
                            </p>
                            <p><label for="file" style="cursor: pointer;">Upload Image</label></p>
                            <p>
                                @if ($logo)
                                    <img id="output" width="400" src="{{ asset('storage/' . $logo->value) }}" alt="Company Logo">
                                @else
                                    <p>No logo uploaded.</p>
                                @endif
                            </p>
                            <button type="submit" class="btn btn-primary">Save Logo</button>
                        </form>
                    </div>
                </div>
            </div><br/>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#submitForm').on('submit', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                var url = '{{ route('setting.save') }}';
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: data,
                    success: function(response) {
                        try {
                            toastr.success('Saved');
                        } catch (error) {
                            alert('Saved');
                        }
                        console.log('Data saved successfully:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving data:', error);
                    }
                });
            });
            $(document).ready(function() {
                $('#openModal').click(function() {
                    $('#exampleModal').modal('show');
                });

                $('#closeModal').click(function() {
                    $('#exampleModal').modal('hide');
                });
            });
        });

        var loadFile = function(event) {
        var image = document.getElementById('output');
        image.src = URL.createObjectURL(event.target.files[0]);
        };
    </script>
@endsection
