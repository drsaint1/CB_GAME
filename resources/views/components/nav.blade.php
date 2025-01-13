
<?php
    $dashLink = '';
    $dashName = "Dashboard";
    $dashIcon = "ni ni-dashboard-fill";
    $dashLink = 'vc/superagent/dashboard';
?>
<div class="nk-sidebar nk-sidebar-fixed is-light " data-content="sidebarMenu">
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a href="{{ getenv('APP_URL').$dashLink }}" class="logo-link nk-sidebar-logo">
                <img src="{{  }}" alt="logo" style="width: 120px;height: 100px;" class="logo-light logo-img">
                <img src="{{ }}" alt="logo" style="width: 120px;height: 100px;" class="logo-dark logo-img">
            </a>
        </div>
        <div class="nk-menu-trigger mr-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu"><em class="icon ni ni-arrow-left"></em></a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
        </div>
    </div><!-- .nk-sidebar-element -->

    <div class="nk-sidebar-element">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu" data-simplebar>
                <ul class="nk-menu">
                    <li class="nk-menu-item">
                        <a href="<?php echo base_url($dashLink); ?>" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon <?php echo $dashIcon; ?>"></em></span>
                            <span class="nk-menu-text"><?php echo $dashName; ?></span>
                        </a>
                    </li><!-- .nk-menu-item -->

                    {{-- this start super agent section --}}
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-user-list"></em></span>
                                <span class="nk-menu-text">Riders</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-report"></em></span>
                                <span class="nk-menu-text">Vehicle</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-wallet-out"></em></span>
                                <span class="nk-menu-text">Request</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-notice"></em></span>
                                <span class="nk-menu-text">Wallet</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-user-alt"></em></span>
                                <span class="nk-menu-text">Settings</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        <li class="nk-menu-item">
                            <a href="" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-signout"></em></span>
                                <span class="nk-menu-text">Logout</span>
                            </a>
                        </li><!-- .nk-menu-item -->
                        {{-- end logistics here --}}
                </ul><!-- .nk-menu -->
            </div><!-- .nk-sidebar-menu -->
        </div><!-- .nk-sidebar-content -->
    </div><!-- .nk-sidebar-element -->
</div>
{{-- sidebar --}}