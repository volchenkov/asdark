@extends('layout')

@section('content')
<div class="row">
    <div class="col-6">
        <div class="alert alert-success" role="alert">
            <strong>Загрузка запланирована!</strong>
        </div>
        <div>
            <p>Состояние загрузки доступно в таблице загрузок</p>
            <a href="/exports">
                <button type="button" class="btn btn-outline-secondary">Перейти к загрузкам</button>
            </a>
        </div>
    </div>
</div>
@endsection
