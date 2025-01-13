<!DOCTYPE html>
<html lang="en" class="js">
<head>
    <meta charset="utf-8">
    <meta name="author" content="Kobosquare">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fav Icon  -->
    <link rel="icon" type="image/png" href="{{ asset('assets/favicon.png'); }}">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Page Title  -->
    <title>{{ config('app.name', 'Logistics') }}</title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="{{ asset('assets/private/css/dashlite.css'); }}">
    <link href="{{ asset('assets/public/lib/toastr/toastr.css'); }}" rel="stylesheet" type="text/css">
    <link id="skin-default" rel="stylesheet" href="{{ asset('assets/private/css/theme.css?ver=2.4.0'); }}">

    <style>
        #notification{
              display: none;
              position: absolute;
              width: 50%;
              z-index: 4000;
          }
          .modal-dialog {
            margin-top: 15px ;
        }
        select{
            wdith:100%;
            display: block;
        }
    </style>
    <script type="text/javascript" src="{{ asset('assets/public/js/jquery.min.js'); }}"></script>
</head>

<body class="nk-body bg-lighter npc-general has-sidebar ">
    <div id="notification" class="alert alert-dismissable text-center"></div>
    <input type="hidden" value="{{ getenv('APP_URL') }}" id='baseurl'>
    
    <div class="nk-app-root">
        <!-- main @s -->
        <div class="nk-main ">
            <!-- sidebar @s -->
            <x-nav />
            <!-- sidebar @e -->

            <!-- wrap @s -->
            <div class="nk-wrap ">
                <!-- main header @s -->
                <div class="nk-header nk-header-fixed is-light">
                    <div class="container-fluid">
                        <div class="nk-header-wrap">
                            <div class="nk-menu-trigger d-xl-none ml-n1">
                                <a href="#" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
                            </div>
                            <div class="nk-header-brand d-xl-none" style="width:5rem;">
                                <a href="<?php echo base_url('admin/dashboard'); ?>" class="logo-link">
                                    <img src="<?php echo base_url('assets/nairaboom_logo.svg'); ?>" alt="" style="width: 400px;height: 60px;" class="logo-dark">
                                </a>
                            </div><!-- .nk-header-brand -->
                            <div class="nk-header-tools">
                                <ul class="nk-quick-nav">
                                    <li class="dropdown user-dropdown">
                                        <a href="#" class="dropdown-toggle mr-n1" data-toggle="dropdown">
                                            <div class="user-toggle">
                                                <div class="user-avatar sm">
                                                    <em class="icon ni ni-user-alt"></em>
                                                </div>
                                                <?php
                                                    $fullname = null;
                                                    if($webSessionManager->getCurrentUserProp('firstname')){
                                                        $firstname = $webSessionManager->getCurrentUserProp('firstname');
                                                        $lastname = ($webSessionManager->getCurrentUserProp('lastname')) ? $webSessionManager->getCurrentUserProp('lastname') : $webSessionManager->getCurrentUserProp('surname');  
                                                        $fullname = $firstname.' '.$lastname; 
                                                    }else{
                                                        $fullname = $webSessionManager->getCurrentUserProp('fullname');
                                                    }
                                                    $adminEmail = $webSessionManager->getCurrentUserProp('email');
                                                     
                                                ?>
                                                <div class="user-info d-none d-xl-block">
                                                    <div class="user-status user-status-active"><?= ($userType == 'admin') ? 'Administator' : 'Superagent'; ?></div>
                                                    <div class="user-name dropdown-indicator"><?php echo $fullname; ?></div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-right">
                                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                                <div class="user-card">
                                                    <div class="user-avatar">
                                                        <span><?php echo formatToNameLabel($fullname,true); ?></span>
                                                    </div>
                                                    <div class="user-info">
                                                        <span class="lead-text"><?php echo $fullname; ?></span>
                                                        <span class="sub-text"><?php echo $adminEmail; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li><a href="#" data-toggle='modal' data-target='#myModalPassword'><em class="icon ni ni-setting"></em><span>Change Password</span></a></li>
                                                    <li data-theme-color="1"><a class="dark-switch" href="#"><em class="icon ni ni-moon"></em><span>Dark Mode</span></a></li>
                                                </ul>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li><a href="<?php echo base_url('logout'); ?>"><em class="icon ni ni-signout"></em><span>Sign out</span></a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div><!-- .nk-header-wrap -->
                    </div><!-- .container-fliud -->
                </div>
                <!-- main header @e -->