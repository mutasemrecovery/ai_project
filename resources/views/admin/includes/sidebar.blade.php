@php
    $u = auth('admin')->user();
@endphp

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-bullseye"></i></div>
        <span class="brand-text">AH Group</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">{{ __('messages.main') }}</div>
        <ul>
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-speedometer2"></i>
                    <span>{{ __('messages.dashboard') }}</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">{{ __('messages.lg_section') }}</div>
        <ul>
            @can('lead-table')
            <li class="nav-item">
                <a href="{{ route('admin.leads.index') }}"
                   class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-bullseye"></i>
                    <span>{{ __('messages.lg_leads') }}</span>
                </a>
            </li>
            @endcan
            @can('campaign-table')
            <li class="nav-item">
                <a href="{{ route('admin.campaigns.index') }}"
                   class="nav-link {{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-megaphone"></i>
                    <span>{{ __('messages.lg_campaigns') }}</span>
                </a>
            </li>
            @endcan
            @can('outreach-table')
            <li class="nav-item">
                <a href="{{ route('admin.outreaches.index') }}"
                   class="nav-link {{ request()->routeIs('admin.outreaches.*') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-send-check"></i>
                    <span>{{ __('messages.lg_outreach') }}</span>
                </a>
            </li>
            @endcan
        </ul>

        <div class="nav-label">{{ __('messages.lg_administration') }}</div>
        <ul>
            @can('employee-table')
            <li class="nav-item">
                <a href="{{ route('admin.employee.index') }}"
                   class="nav-link {{ request()->routeIs('admin.employee.*') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-person-badge"></i>
                    <span>{{ __('messages.employees') }}</span>
                </a>
            </li>
            @endcan
            @can('role-table')
            <li class="nav-item">
                <a href="{{ route('admin.role.index') }}"
                   class="nav-link {{ request()->routeIs('admin.role.*') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-shield-lock"></i>
                    <span>{{ __('messages.Roles') }}</span>
                </a>
            </li>
            @endcan
        </ul>
    </nav>

    <div class="sidebar-footer">
        <ul>
            <li class="nav-item">
                <a href="{{ route('admin.login.edit', auth('admin')->id()) }}" class="nav-link">
                    <i class="nav-icon bi bi-gear"></i>
                    <span>{{ __('messages.settings') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link"
                   onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    <i class="nav-icon bi bi-box-arrow-right"></i>
                    <span>{{ __('messages.sign_out') }}</span>
                </a>
            </li>
        </ul>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="{{ __('messages.collapse_sidebar') }}">
            <i class="bi bi-arrow-bar-left"></i>
        </button>
    </div>

</aside>
