@extends('layout')

@section('content')
<div class="row">
    <div class="col-12">
        <p>Таблица создана, отредактируйте ее (одна строка - одно объявление) и начните загрузку кнопкой ниже.</p>
    </div>
</div>
<form action="/exports_start" method="POST">
    @csrf
    <div class="row">
        <div class="col-12 form-group">
            <a href="https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}" target="_blank">https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}</a>
        </div>
    </div>
    <div class="row">
        <div class="col-2 form-group">
            <input class="btn btn-primary"
                   value="Начать загрузку в ВК"
                   type="submit"/>
        </div>
    </div>
</form>
@endsection
