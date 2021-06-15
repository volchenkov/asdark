<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ASDARK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">

    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12"></script>

    <script src="https://unpkg.com/vue-select@3.0.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-select@3.0.0/dist/vue-select.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

    <link rel="stylesheet" type="text/css" href="{{ asset('/css/styles.css') }}" />

    <style>
        html {
            position: relative;
            min-height: 100%;
        }
        body {
            margin-bottom: 60px;
            padding-top: 3.5rem;
        }
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60px;
            line-height: 60px;
        }
        .text-underlined {
            text-decoration: underline;
        }
    </style>

    <script type="text/javascript">
        Vue.component('v-select', VueSelect.VueSelect);
    </script>
</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container">
        <button class="navbar-toggler collapsed"
                type="button"
                data-toggle="collapse"
                data-target="#navbarTop"
                aria-controls="navbarTop"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse collapse" id="navbarTop" style="">
            <ul class="navbar-nav me-auto">
                <a href="/" class="navbar-brand"> <strong>ASDARK</strong></a>
                <li class="nav-item">
                    <a href="{{ route('adsEdit.start') }}"
                       class="nav-link {{ request()->is('ads_edit*') ? 'active' : '' }}">редактирование объявлений</a>
                </li>
                <li class="nav-item">
                    <a href="/exports"
                       class="nav-link {{ request()->is('export*') ? 'active' : '' }}">загрузки</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-md-auto">
                <li class="nav-item">
                    <a href="/logout" class="nav-link">выход</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main role="main" class="container mt-4">
    @include('toasts')
    @yield('content')
</main>
<footer class="footer bg-light">
    <div class="container">
        <a href="/help"
           class="me-3 text-muted {{ (request()->is('help*')) ? 'text-underlined' : '' }}">справка</a>
        <a href="/vk_auth_current_state"
           class="text-muted {{ (request()->is('vk_auth*')) ? 'text-underlined' : '' }}">подключение ВК</a>
    </div>
</footer>
</body>
</html>
