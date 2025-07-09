 <!-- Required meta tags -->
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <!--favicon-->
 <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png" />
 <!--plugins-->
 <link href="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
 <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
 <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
 <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
 <!-- loader-->
 <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />
 <script src="{{ asset('assets/js/pace.min.js') }}"></script>
 <!-- Bootstrap CSS -->
 <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
 <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
 <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
 <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
 <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
 <!-- Theme Style CSS -->
 <link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
 <link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
 <link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
 <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />


 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
     integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
     crossorigin="anonymous" referrerpolicy="no-referrer"></script>
 <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
 <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
 <!-- Toastr CSS -->
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

 <!-- Toastr JS -->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
 <title>{{ env('CREDIT', 'Dashboard') }}</title>
 <link rel="stylesheet" href="{{ asset('dist/css/demo.css') }}">
 <link rel="stylesheet" href="{{ asset('dist/css/dropify.min.css') }}">

 <style>
     .select2-selection__rendered {
         line-height: 35px !important;
     }

     .select2-container .select2-selection--single {
         height: 37px !important;
         width: 100% !important;
     }

     .select2-selection__arrow {
         height: 37px !important;
     }

     .horizontal>li>a {
         font-size: 16px !important;
         color: #fff;
         text-decoration: none;
         padding: 4px 15px;
         margin: 2px 15px !important;
         border-radius: 5px;
         display: block;
         transition: background-color 0.3s ease;
     }

     .horizontal>li>a:hover {
         background-color: #000000;
         color: #FFFFFF !important;
     }

     .horizontal>li>a.active {
         background-color: #000000;
         color: #fff;
     }

     :is(a.nav-link:hover, a.nav-link.active) * {
         color: #0C0101 !important;
     }

     :is(a.nav-link:hover, a.nav-link.active) * {
         color: #050505 !important;
     }

     .chart-container-1 {
         position: relative;
         height: 200px !important;
     }

     .card-footer {
         padding: .5rem 1rem;
         background-color: rgb(255 255 255 / 3%);
         border-top: 1px solid rgba(0, 0, 0, .125);
     }

     .lds-roller,
     .lds-roller div,
     .lds-roller div:after {
         box-sizing: border-box;
     }

     .lds-roller {
         display: block;
         width: 100px;
         height: 100px;
         left: 40%;
         top: 35%;
         z-index: 59;
         position: absolute;
     }

     .lds-roller div {
         animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
         transform-origin: 40px 40px;
     }

     .lds-roller div:after {
         content: " ";
         display: block;
         position: absolute;
         width: 7.2px;
         height: 7.2px;
         border-radius: 50%;
         background: currentColor;
         margin: -3.6px 0 0 -3.6px;
     }

     .lds-roller div:nth-child(1) {
         animation-delay: -0.036s;
     }

     .lds-roller div:nth-child(1):after {
         top: 62.62742px;
         left: 62.62742px;
     }

     .lds-roller div:nth-child(2) {
         animation-delay: -0.072s;
     }

     .lds-roller div:nth-child(2):after {
         top: 67.71281px;
         left: 56px;
     }

     .lds-roller div:nth-child(3) {
         animation-delay: -0.108s;
     }

     .lds-roller div:nth-child(3):after {
         top: 70.90963px;
         left: 48.28221px;
     }

     .lds-roller div:nth-child(4) {
         animation-delay: -0.144s;
     }

     .lds-roller div:nth-child(4):after {
         top: 72px;
         left: 40px;
     }

     .lds-roller div:nth-child(5) {
         animation-delay: -0.18s;
     }

     .lds-roller div:nth-child(5):after {
         top: 70.90963px;
         left: 31.71779px;
     }

     .lds-roller div:nth-child(6) {
         animation-delay: -0.216s;
     }

     .lds-roller div:nth-child(6):after {
         top: 67.71281px;
         left: 24px;
     }

     .lds-roller div:nth-child(7) {
         animation-delay: -0.252s;
     }

     .lds-roller div:nth-child(7):after {
         top: 62.62742px;
         left: 17.37258px;
     }

     .lds-roller div:nth-child(8) {
         animation-delay: -0.288s;
     }

     .lds-roller div:nth-child(8):after {
         top: 56px;
         left: 12.28719px;
     }

     @keyframes lds-roller {
         0% {
             transform: rotate(0deg);
         }

         100% {
             transform: rotate(360deg);
         }
     }
 </style>
