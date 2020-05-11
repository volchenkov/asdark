@extends('layout')

@section('content')
<div class="row">
    <div class="col-12">
        <p>Внесите ID документа, по которому будут загружены объявления. Одна строка - одно объявление.</p>
    </div>
</div>
<form action="/cds_start_export" method="POST">
    @csrf
    <div class="row">
        <div class="col-10 form-group">
            <input class="form-control"
                   type="text"
                   id="spreadsheetId"
                   name="spreadsheetId"
                   value="{{ $spreadsheetId }}"
                   placeholder="ID документа, например 1J0Pzil0DSMKsf_HWh-uCThzLufSntclZGqKEFCl7QbB"
                   required/>
        </div>
        @if ($spreadsheetId)
        <div class="col-2 form-group">
            <a href="https://docs.google.com/spreadsheets/d/{{$spreadsheetId}}" target="_blank">
                <button type="button" class="btn btn btn-outline-secondary">
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
