<!doctype html>
<html lang="en">
<head>
@include('admin.layouts.head')
@yield('css')
</head>
<body>
	<!--wrapper-->
	<div class="wrapper">

          @include('admin.layouts.header')
          @include('admin.layouts.sidebar')
          <div class="page-wrapper">
          <div class="page-content">
          @yield('content')
          </div>
         </div>
         @include('admin.layouts.footer')
        @include('admin.layouts.footer_section')
</div>
@yield('scripts')
</body>
</html>
