@extends('layout')

@section('content')
<div class="row">
    <div class="col-6">
        <div class="alert alert-success" role="alert">
           Загрузка запущена!
        </div>
        <p>Результаты загрузки каждого из объявлений будут помещены в отдельный столбец в таблице.</p>
        <div>
            <a href="{{$resource}}" target="_blank">
                <button type="button" class="btn btn-link">
                    Перейти к таблице
                </button>
            </a>
        </div>
    </div>
</div>
@endsection
