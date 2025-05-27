@extends('autoauth.public')
@section('title', 'Connecting')
@section('js')
    <script>
        $(document).ready(function() {
            console.log(window.location);
            let params = new URLSearchParams(window.location.search);
            console.log(params);
            let dt = {
                location: params.get('location') || "",
                token: params.get('token') || "",
            }
            console.log(dt);

            if ((dt.token ?? "") != "" && (dt.location ?? "") != "") {
                checkForauth(dt);
            }
        });

        function checkForauth(dt) {
            console.log(dt + 'Ye new wala ha ')
            Swal.fire({
                title: "Authenticating...",
                timerProgressBar: true,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
           console.log("hi");
            var url = "{{ route('admin.auth.checking') }}";
            $.ajax({
                url: url,
                type: 'GET',
                data: {
                    location: dt.location,
                    token: dt.token
                },
                success: function(data) {

                    if (data.is_crm == true) {
                        Swal.close();
                        localStorage.setItem('token-id', data.token_id);
                        toastr.success("Location connected successfully!");
                        location.href = "{{ route('admin.dashboard') }}?v=" + new Date().getTime();
                    } else {
                        Swal.fire({
                            title: "Unable to auth user"
                        })
                    }

                },
                error: function(data) {

                    Swal.fire({
                        title: "Unable to auth user"
                    })

                },
                complete: function(data) {
                    console.log("completion : " + data);

                }
            });
        }
    </script>
@endsection
