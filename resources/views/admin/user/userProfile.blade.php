@extends('admin.layouts.index')
@section('content')

    <!--end breadcrumb-->
    <div class="row">
        <div class="col-md-12 mx-auto">
            <div class="card mb-5 mb-xl-10">
                <!--begin::Card header-->
                <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
                    data-bs-target="#kt_account_profile_details" aria-expanded="true"
                    aria-controls="kt_account_profile_details">
                    <!--begin::Card title-->
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">General Details</h3>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--begin::Card header-->
                <!--begin::Content-->
                <div id="kt_account_settings_profile_details" class="collapse show">
                    <!--begin::Form-->
                    <form action="{{ route('admin.profile.save') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <!--begin::Card body-->
                        <div class="card-body border-top p-9">
                        <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Avatar</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Image input-->
                                    @php
                                    // Construct the default image URL
                                    $defaultImage = $user->image ? asset($user->image) : asset('assets/media/avatars/blank.png');
                                    @endphp
                                <input type="file" id="input-file-now" name="image" class="dropify" data-default-file="{{ $defaultImage }}" />
                                    <!--end::Image input-->
                                    <!--begin::Hint-->
                                    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                    <!--end::Hint-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group--->
                            <!--begin::Input group-->
                            <div class="row mb-2">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Full Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Row-->
                                    <div class="row">
                                        <!--begin::Col-->
                                        <div class="col-lg-6 fv-row fv-plugins-icon-container">
                                            <input type="text" name="name"
                                                class="form-control form-control-lg form-control-solid mb-3 mb-lg-0"
                                                placeholder="First name" value="{{ old('name', $user->name) }}">
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-lg-6 fv-row fv-plugins-icon-container">
                                            <input type="text" name="lname"
                                                class="form-control form-control-lg form-control-solid"
                                                placeholder="Last name" value="{{ old('lname', $user->last_name) }}">
                                            <div class="fv-plugins-message-container invalid-feedback"></div>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Email</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-4 fv-row fv-plugins-icon-container">
                                    <input type="text"
                                        class="form-control form-control-lg form-control-solid" placeholder="Email"
                                        value="{{ old('email', $user->email) }}" readonly>
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Card body-->
                        <!--begin::Actions-->
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="submit" class="btn btn-primary" >Save
                                Changes</button>
                        </div>
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Content-->
            </div>

            {{-- passwords --}}
            <div class="card mb-5 mb-xl-10">
                <!--begin::Card header-->
                <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
                    data-bs-target="#kt_account_signin_method">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Sign-in Method</h3>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Content-->
                <div id="kt_account_settings_signin_method" class="collapse show">
                    <!--begin::Card body-->
                    <div class="card-body border-top p-9">
                        <!--begin::Email Address-->
                        <div class="d-flex flex-wrap align-items-center">
                            <!--begin::Label-->
                            <div id="kt_signin_email" class="">
                                <div class="fs-6 fw-bold mb-1">Email Address</div>
                                <div class="fw-semibold text-gray-600">{{ $user->email ?? 'user@example.com' }}</div>
                            </div>
                        </div>
                        <!--end::Email Address-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-6"></div>
                        <!--end::Separator-->
                        <!--begin::Password-->
                        <div class="d-flex flex-wrap align-items-center mb-10">
                            <!--begin::Label-->
                            <div id="kt_signin_password" class="">
                                <div class="fs-6 fw-bold mb-1">Password</div>
                                <div class="fw-semibold text-gray-600">************</div>
                            </div>
                            <!--end::Label-->

                            <!--begin::Action-->
                            <!--end::Action-->
                        </div>
                        <!--end::Password-->
                        <!--begin::Notice-->
                        <!--begin::Action-->

                        <!--end::Action-->
                        <!--end::Notice-->
                    </div>
                    <!--end::Card body-->
                    <div class="d-flex pb-2 px-2">
                        <div id="kt_signin_password_button">
                            <button class="btn btn-primary btn-active-light-primary mx-2">Reset Password</button>
                        </div>
                        <div id="kt_signin_email_button" class="">
                            <button class="btn btn-primary btn-active-light-primary">Change Email</button>
                        </div>
                    </div>
                </div>
                <!--end::Content-->
            </div>
            <!--end::Label-->
            <!--begin::Edit-->
            <div class="card mb-5 mb-xl-10">
                <div id="kt_signin_email_edit" class="flex-row-fluid d-none">
                    <!--begin::Form-->
                    <form id="kt_signin_change_email" class="form fv-plugins-bootstrap5 fv-plugins-framework"
                        novalidate="novalidate" method="POST" action="{{ route('admin.email.save') }}">
                        @csrf
                        <div class="row mb-6 p-3">
                            <div class="col-lg-6 mb-4 mb-lg-0">
                                <div class="fv-row mb-0 fv-plugins-icon-container">
                                    <label for="emailaddress" class="form-label fs-6 fw-bold mb-3">Enter New
                                        Email Address</label>
                                    <input type="email" class="form-control form-control-lg form-control-solid"
                                        id="emailaddress" placeholder="Email Address" name="email"
                                        value="{{ old('email', $user->email) }}">
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-0 fv-plugins-icon-container">
                                    <label for="confirmemailpassword" class="form-label fs-6 fw-bold mb-3">Confirm
                                        Password</label>
                                    <input type="password" class="form-control form-control-lg form-control-solid"
                                        name="password" id="confirmemailpassword" value="{{ old('password') }}">
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex p-4">
                            <button id="" type="submit" class="btn btn-primary me-2 px-6">Update
                                Email</button>
                            <button id="kt_signin_cancel" type="button"
                                class="btn btn-color-gray-400 btn-active-light-primary px-6">Cancel</button>
                        </div>
                    </form>
                    <!--end::Form-->
                </div>
            </div>
            <!--end::Edit-->

             <!--begin::Edit-->
             <div class="card mb-5 mb-xl-10">
             <div id="kt_signin_password_edit" class="flex-row-fluid d-none p-3">
                <!--begin::Form-->
                <form id="kt_signin_change_password"
                    class="form fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate"
                    method="POST" action="{{ route('admin.password.save') }}">
                    @csrf
                    <div class="row mb-1">
                        <div class="col-lg-4">
                            <div class="fv-row mb-0 fv-plugins-icon-container">
                                <label for="currentpassword" class="form-label fs-6 fw-bold mb-3">Current
                                    Password</label>
                                <input type="password"
                                    class="form-control form-control-lg form-control-solid"
                                    name="current_password" id="currentpassword">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="fv-row mb-0 fv-plugins-icon-container">
                                <label for="newpassword" class="form-label fs-6 fw-bold mb-3">New
                                    Password</label>
                                <input type="password"
                                    class="form-control form-control-lg form-control-solid"
                                    name="password" id="newpassword">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="fv-row mb-0 fv-plugins-icon-container">
                                <label for="confirmpassword" class="form-label fs-6 fw-bold mb-3">Confirm
                                    New Password</label>
                                <input type="password"
                                    class="form-control form-control-lg form-control-solid"
                                    name="confirm_password" id="confirmpassword">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-text mb-5">Password must be at least 8 character and contain symbols
                    </div>
                    <div class="d-flex">
                        <button id="kt_password_submit" type="submit"
                            class="btn btn-primary me-2 px-6">Update Password</button>
                        <button id="kt_password_cancel" type="button"
                            class="btn btn-color-gray-400 btn-active-light-primary px-6">Cancel</button>
                    </div>
                </form>
                <!--end::Form-->
            </div>
        </div>
            <!--end::Edit-->

        </div>
    </div>
    <script>
          var loadFile = function(event) {
          var image = document.getElementById('output');
          image.src = URL.createObjectURL(event.target.files[0]);
          };

        $(document).ready(function() {
            $('#kt_signin_email_button button').on('click', function() {
                $('#kt_signin_email_button').addClass('d-none');
                $('#kt_signin_email_edit').removeClass('d-none');
            });
            $('#kt_signin_cancel').on('click', function() {
                $('#kt_signin_email_edit').addClass('d-none');
                $('#kt_signin_email_button').removeClass('d-none');
            });

            $('#kt_signin_password_button button').on('click', function() {
                $('#kt_signin_password_button').addClass('d-none');
                $('#kt_signin_password_edit').removeClass('d-none');
            });

            $('#kt_password_cancel').on('click', function() {
                $('#kt_signin_password_edit').addClass('d-none');
                $('#kt_signin_password_button').removeClass('d-none');
            });
        });
    </script>
@endsection


<script src="{{ asset('assets/js/app.js') }}"></script>
