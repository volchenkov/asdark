@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        <p>Произошла ошибка. Пожалуйста, повторите снова или обратитесь к администратору с информацией ниже.</p>
        <div class="alert alert-warning" role="alert">
            {{ $message }}
        </div>
    </div>
</div>
@endsection
