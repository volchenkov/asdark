@extends('layout')

@section('content')
<div class="row mb-2">
    <div class="col-12">
        <div>Таблица создана и доступна для редактирования. Одна строка - одно объявление.</div>
        <div>Чтобы начать загрузку в ВК, воспользуйтесь кнопкой ниже.</div>
    </div>
</div>
<div class="row mb-2">
    <div class="col-12 my-4">
        <div>Открыть таблицу в новой вкладке: </div>
        <a href="https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}" target="_blank">https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}</a>
    </div>
    <div class="col-12">
        <iframe frameborder="0" style="width: 100%; height: 600px; border: 1px solid #5a5"
                src="https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}?rm=minimal">
            Быстрое редактирование недоступно
        </iframe>
        <div class="text-muted small"><strong>Ctrl + F</strong> - найти, <strong>Ctrl + H</strong> - найти и заменить</div>
    </div>
</div>

<form action="/exports_start" method="POST">
    @csrf
    <input type="hidden" name="spreadsheetId" value="{{ $spreadsheetId }}">
    <div class="row mt-4">
        <div class="col-2 form-group">
            <input class="btn btn-primary"
                   value="Начать загрузку в ВК"
                   type="submit"/>
        </div>
    </div>
</form>
@endsection
