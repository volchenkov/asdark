@extends('layout')

@section('content')
<form action="/exports_start" method="POST">
    @csrf
    <input type="hidden" name="spreadsheetId" value="{{ $export['sid'] }}">
    <input type="hidden" name="captcha" value="{{ $export['captcha'] }}">
    <div class="row">
        <div class="col-12 form-group">
            <h3>Капча для загрузки #{{ $export['id'] }}</h3>
            <div>При обновлении страницы капча обновляется, прежняя становится недействительной.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-4 form-group">
            <img src="{{ $export['captcha'] }}">
        </div>
    </div>

    <div class="row">
        <div class="col-4 form-group">
            <input class="form-control"
                   required
                   id="captcha_code"
                   name="captcha_code"
                   placeholder="Введите символы с картинки"/>
        </div>
    </div>
    <div class="row">
        <div class="col-2 form-group">
            <input class="btn btn-primary"
                   value="повторить загрузку"
                   type="submit"/>
        </div>
    </div>

</form>
@endsection
