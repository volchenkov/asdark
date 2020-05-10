@extends('layout')

@section('content')
<form action="/cds_start_export" method="GET">
    <div class="row text-center">
        <div class="col-10 form-group">
            <input class="form-control"
                   type="url"
                   pattern="https://docs.google.com/spreadsheets/d/.*"
                   name="spreadsheet"
                   value="{{ $resource }}"
                   placeholder="Ссылка на таблицу, eg https://docs.google.com/spreadsheets/d/somespreadsheetId"
                   required/>
        </div>
        @if ($resource)
        <div class="col-2 form-group">
            <a href="{{$resource}}" target="_blank">
                <button type="button" class="btn btn-link">
                    Посмотреть
                </button>
            </a>
        </div>
        @endif
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
