@extends('layout')

@section('content')
    <div class="row">
        <div class="col-12">
            <p>Сервис используется для массового редактирования объявлений ВК</p>
        </div>
        <div class="col-6">
            <div class="alert alert-info">
                Для полноценной работы требуется подключение к ВК
                <div class="small"><a href="/vk_auth_current_state">Здесь</a> можно узнать текущее состояние подключения или обновить его</div>
            </div>
        </div>
        <div class="col-12">
            <p>Чтобы начать редактирование, перейдите <a href="/ads_edit_choose_client">сюда</a>.
        </div>
    </div>
@endsection
