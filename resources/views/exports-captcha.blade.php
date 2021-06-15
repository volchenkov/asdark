@extends('layout')

@section('content')
<form action="{{ route('export.rerun', ['id' => $export['id']]) }}" method="POST">
    @csrf
    <input type="hidden" name="captcha" value="{{ $export['captcha'] }}">
    <div class="row mb-2">
        <div class="col-12">
            <h3>Капча для загрузки #{{ $export['id'] }}</h3>
            <div>При обновлении страницы капча обновляется, прежняя становится недействительной.</div>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-4">
            <img src="{{ $export['captcha'] }}">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-4">
            <input class="form-control"
                   required
                   id="captcha_code"
                   name="captcha_code"
                   placeholder="Введите символы с картинки"/>
        </div>
    </div>

    <div class="row">
        <div class="col-2">
            <input class="btn btn-primary"
                   value="продолжить загрузку"
                   type="submit"/>
        </div>
    </div>

</form>
@endsection
