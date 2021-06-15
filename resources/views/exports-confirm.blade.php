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
</div>

<form action="{{ route('export.start') }}" method="POST">
    @csrf
    <input type="hidden" name="spreadsheetId" value="{{ $spreadsheetId }}">
    <div class="row my-4">
        <div class="col-2">
            <input class="btn btn-primary"
                   value="начать загрузку в ВК"
                   type="submit"/>
        </div>
    </div>
</form>
@endsection
