<!--sidebar wrapper -->
<div class="sidebar-wrapper" data-simplebar="true">
			<div class="sidebar-header">
                @php
                $logo = App\Models\Setting::where('key', 'logo')->first();
                @endphp
				<div>
                    <img width="40" src="{{ $logo ? asset('assets/uploads/company-logos' . $logo->value) : asset('assets/uploads/company-logos/company-logo.png') }}" alt="Company Logo">

				</div>

				<div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
				</div>
			</div>
			<!--navigation-->
			<ul class="metismenu" id="menu">
				<li>
                    <a href="{{route('admin.dashboard')}}">
						<div class="parent-icon"><i class="bx bx-home-circle"></i>
						</div>
                        <div class="menu-title">Dashboard</div>
					</a>
				</li>
				@if (is_role() == 'superadmin')
                    <li>
					<a href="{{route('admin.user.index')}}">
						<div class="parent-icon"><i class="bx bx-user-circle"></i>
						</div>
						<div class="menu-title">User</div>
					</a>
				</li>
                @endif
                @if(is_role() == 'superadmin' )
				<li>
					<a href="{{route('setting.index')}}">
						<div class="parent-icon"><i class="fadeIn animated bx bx-badge"></i>
						</div>
						<div class="menu-title">Settings</div>
					</a>
				</li>
				@endif
                @if(is_role() == 'company' || is_role() == 'admin')
                <li>
                    <a href="{{ route('admin.agents.index') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-id-card"></i></div>
                        <div class="menu-title">Agents</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.campaigns.index') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-bar-chart-alt-2"></i></div>
                        <div class="menu-title">Campaigns</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.sent.contact') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-send"></i></div>
                        <div class="menu-title">Sent Contacts</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reserve.contact') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-archive-in"></i></div>
                        <div class="menu-title">Reserve Contacts</div>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.log.index') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-error"></i></div>
                        <div class="menu-title">Logs</div>
                    </a>
                </li>

                {{-- <li>
                    <a href="{{ route('admin.states.index') }}">
                        <div class="parent-icon"><i class="fadeIn animated bx bx-map"></i></div>
                        <div class="menu-title">States</div>
                    </a>
                </li> --}}

                @endif

                <li>
                    <a href="{{ route('logout') }}"
                    onclick="event.preventDefault();
                                    document.getElementById('logout-form').submit();">
						<div class="parent-icon"><i class="fadeIn animated bx bx-log-out"></i>
						</div>
						<div class="menu-title">Logout</div>
					</a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
				</li>
			</ul>
			<!--end navigation-->
		</div>
		<!--end sidebar wrapper -->
