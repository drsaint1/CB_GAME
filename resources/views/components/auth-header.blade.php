<!DOCTYPE html>
<html lang="zxx" class="js">

<head>
    <meta charset="utf-8">
    <meta name="author" content="Softnio">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Kobosquare">
    <!-- Fav Icon  -->
    <link rel="apple-touch-icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/apple-touch-icon.png') }}">
    <link rel="shortcut icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32x32.png') }}">
    <link rel="shortcut icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset("asstes/favicon/site.webmanifest")}}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('assets/favicon/ms-icon-144x144.png')}}">
    <meta name="theme-color" content="#ffffff">
    <!-- Page Title  -->
    <title>{{ $title }} | Kobosquare</title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="{{ asset('assets/public/css/dashlite.css?ver=2.4.0') }}">
    <link id="skin-default" rel="stylesheet" href="{{ asset('assets/public/css/theme.css?ver=2.4.0') }}">
</head>

<body class="nk-body bg-white npc-default pg-auth">
    <div class="nk-app-root">
        <!-- main @s -->
        <div class="nk-main ">
            <!-- wrap @s -->
            <div class="nk-wrap nk-wrap-nosidebar">
                <!-- content @s -->
                <div class="nk-content ">
                    <div class="nk-block nk-block-middle nk-auth-body  wide-xs">
                        <x-common.logo class='text-center' />
                        <div class="card">
                            <div class="card-inner card-inner-lg">
                                {{ $slot }}
                            </div>
                        </div>
                    </div><!-- nk-split -->
                </div>
                <!-- wrap @e -->
            </div>
            <!-- content @e -->
        </div>
        <!-- main @e -->
    </div>
    <!-- app-root @e -->
    <!-- JavaScript -->
    <script src="{{ asset('assets/public/js/bundle.js?ver=2.4.0') }}"></script>
    <script src="{{ asset('assets/public/js/scripts.js?ver=2.4.0') }}"></script>

</html>