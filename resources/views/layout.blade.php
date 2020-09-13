<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ASDARK</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
            integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
            integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
            crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
            integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6"
            crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/vue"></script>

    <script src="https://unpkg.com/vue-select@3.0.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-select@3.0.0/dist/vue-select.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
        }
        body {
            padding-top: 3.5rem;
        }
    </style>

    <script type="text/javascript">
        Vue.component('v-select', VueSelect.VueSelect);
    </script>
</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
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
        <ul class="navbar-nav mr-auto">
            <a href="/" class="navbar-brand"> <strong>ASDARK</strong></a>
            <li class="nav-item">
                <a href="{{ route('adsEdit.start') }}"
                   class="nav-link {{ request()->is('ads_edit*') ? 'active' : '' }}">Редактирование объявлений</a>
            </li>
            <li class="nav-item">
                <a href="/exports"
                   class="nav-link {{ request()->is('export*') ? 'active' : '' }}">Загрузки</a>
            </li>
            <li class="nav-item">
                <a href="/help"
                   class="nav-link {{ (request()->is('help*')) ? 'active' : '' }}">Справка</a>
            </li>
            <li class="nav-item">
                <a href="/vk_auth_current_state"
                   class="nav-link {{ (request()->is('vk_auth*')) ? 'active' : '' }}">Подключение ВК</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-md-auto">
            <li class="nav-item">
                <a href="/logout" class="nav-link">Выход</a>
            </li>
        </ul>
    </div>
</nav>
<main role="main" class="container mt-4">
    @include('toasts')
    @yield('content')
</main>
</body>
</html>
