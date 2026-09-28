<nav class="navbar" id="navbar">

    {{-- Hamburger (mobile) --}}
    <button class="navbar-toggler"
            id="sidebarToggler"
            aria-label="{{ __('messages.toggle_sidebar') }}">
        <i class="bi bi-list"></i>
    </button>

    {{-- Search --}}
    <div class="navbar-search">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text"
                   placeholder="{{ __('messages.search_placeholder') }}"
                   aria-label="{{ __('messages.search_placeholder') }}">
        </div>
    </div>

    {{-- Right actions --}}
    <div class="navbar-end">

        <a href="#"
           class="icon-btn"
           title="{{ __('messages.notifications') }}">
            <i class="bi bi-bell"></i>
            <span class="dot"></span>
        </a>

        @foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties)
            @if ($locale !== app()->getLocale())
                <a href="{{ LaravelLocalization::getLocalizedURL($locale, null, [], true) }}"
                   class="icon-btn"
                   hreflang="{{ $locale }}">
                    {{ strtoupper($locale) }}
                </a>
            @endif
        @endforeach

        <div class="nav-divider"></div>

        @php
            $authAdmin  = auth('admin')->user();
            $adminName  = $authAdmin?->name ?? 'Admin';
            $adminLetter = strtoupper(mb_substr($adminName, 0, 1));
            $adminRole  = $authAdmin?->is_super
                ? __('messages.administrator')
                : ($authAdmin?->roles->first()?->name ?? __('messages.employee'));
        @endphp

        <div class="dropdown">
            <div class="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">{{ $adminLetter }}</div>

                <div class="user-info">
                    <span class="user-name">{{ $adminName }}</span>
                    <span class="user-role">{{ $adminRole }}</span>
                </div>

                <i class="bi bi-chevron-down ms-1"
                   style="font-size:.65rem;color:var(--muted)"></i>
            </div>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border"
                style="border-radius:12px;min-width:180px;font-size:.845rem;">

                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.login.edit', $authAdmin?->id) }}">
                        <i class="bi bi-person-circle" style="color:var(--muted)"></i>
                        {{ __('messages.my_profile') }}
                    </a>
                </li>

                <li><hr class="dropdown-divider my-1"></li>

                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger"
                       href="#"
                       onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        {{ __('messages.sign_out') }}
                    </a>

                    <form id="admin-logout-form"
                          action="{{ route('admin.logout') }}"
                          method="POST"
                          class="d-none">
                        @csrf
                    </form>
                </li>

            </ul>
        </div>

    </div>
</nav>
