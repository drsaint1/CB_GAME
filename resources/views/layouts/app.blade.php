@include('layouts.header')
<body class="nk-body bg-white npc-default pg-auth">
    <div class="nk-app-root">
        <!-- main @s -->
        <div class="nk-main ">
@yield('contents')
        </div>
    </div>
</body>
@include('layouts.footer')
