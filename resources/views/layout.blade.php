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
    </head>
    <body>
        <header>
            <div class="collapse bg-dark" id="navbarHeader">
                <div class="container">
                    <div class="row pt-4">
                        @if($vkAccount)
                        <div class="col-12 my-1">
                            <a href="/exports_confirm" class="text-white mr-3">Запустить загрузку</a>
                            <a href="/exports" class="text-white mr-3">Загрузки</a>
                        </div>
                        <div class="col-12 mb-3">
                            <a href="/cds_form" class="small text-white mr-3">Создание объявлений ЦДС</a>
                            <a href="/ads_edit_form" class="small text-white mr-3">Редактирование объявлений</a>
                        </div>
                        @endif

                        <div class="col-12 my-2">
                            <a href="/vk_auth" class="text-white">Подключить ВК</a>
                            @if($vkAccount)
                            <span class="text-muted"> - сейчас подключен <span title="Аккаунт">{{ $vkAccount }}</span> {{ $vkClientId ? "( клиент".$vkClientId.")" : '' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar navbar-dark bg-dark shadow-sm">
                <div class="container d-flex justify-content-between">
                    <a href="/"
                       data-toggle="collapse"
                       data-target="#navbarHeader"
                       aria-controls="navbarHeader"
                       aria-expanded="false"
                       aria-label="Toggle navigation"
                       class="navbar-brand d-flex align-items-center">
                        <strong>ASDARK</strong>
                    </a>
                    <button class="navbar-toggler"
                            type="button"
                            data-toggle="collapse"
                            data-target="#navbarHeader"
                            aria-controls="navbarHeader"
                            aria-expanded="false"
                            aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </div>
        </header>
        <div class="container pt-5">
            @yield('content')
        </div>
    </body>
</html>
