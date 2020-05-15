@extends('layout')

@section('content')
<div class="row">
    <div class="col-6">
        <div class="alert alert-success" role="alert">
           Загрузка запланирована!
        </div>
        <p></p>
        <p>Результаты загрузки объявлений будут помещены в отдельный столбец в таблице.</p>
        <div>
            <a href="https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}" target="_blank">
                <button type="button" class="btn btn-outline-secondary">
                    Перейти к таблице
                </button>
            </a>
        </div>
    </div>
</div>
@endsection
