@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if ($ok)
            <p>Сохраните информацию, приведенную ниже. Обратитесь с ней к администатору сайта для завершения подключения.</p>
            <div class="alert alert-success" role="alert">
                {{ $vkResponse }}
            </div>
            <p><strong>Токен дает доступ к кабинету - не передавайте его никому другому!</strong></p>
        @else
            <p>Произошла ошибка. Пожалуйста, предоставьте информацию ниже администратору сайта.</p>
            <div class="alert alert-warning" role="alert">
                {{ $vkResponse }}
            </div>
        @endif
    </div>
</div>
@endsection
