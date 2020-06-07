<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ASDARK</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <style>
            html,body {
                height: 100%;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid h-100">
            <div class="row h-100">
                <div class="col-2 navbar-dark bg-dark shadow-sm pt-5">
                    <div class="mb-3">
                        <a href="/"
                           class="navbar-brand">
                            <strong>ASDARK</strong>
                        </a>
                    </div>

                    <ul class="nav flex-column flex-nowrap overflow-hidden">
                        <li class="nav-item">
                            <a href="/vk_auth"
                               class="nav-linksmall text-white mr-3">Подключить ВК</a>
                        </li>
                        @if($vkAccount)
                            <span class="text-muted">подключен <span title="Аккаунт">{{ $vkAccount }}</span> {{ $vkClientId ? "( клиент".$vkClientId.")" : '' }}</span>
                        @endif

                        @if($vkAccount)
                            <li class="nav-item">
                                <a href="/exports_confirm"
                                   class="nav-linksmall text-white mr-3">Запустить загрузку</a>
                            </li>
                            <li class="nav-item">
                                <a href="/exports"
                                   class="nav-linksmall text-white mr-3">Загрузки</a>
                            </li>
                            <li class="nav-item">
                                <a href="/ads_edit_choose_client"
                                   class="nav-linksmall text-white mr-3">Редактирование объявлений</a>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="col pt-5">
                    @yield('content')
                </div>
                <div class="col-1 pt-5">
                </div>
            </div>
        </div>
    </body>
</html>
