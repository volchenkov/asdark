@extends('layout')

@section('content')
    <div class="row">
        <div class="col-md-6 form-group">
            @if ($connection)
                <p>ВК подключен</p>
                <p>
                    @if(isset($connection->data['account_id']))
                        Выбран {{$connection->data['account_name']}} #{{$connection->data['account_id']}}
                    @else
                        Аккаунт не выбран
                    @endif
                </p>
                <p class="small text-muted">Обновлено {{ $connection->updated_at }}</p>
            @else
                <p>ВК пока не подключен</p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <a href="/vk_auth" class="btn btn-primary" tabindex="-1" role="button" aria-disabled="true">{{ $connection ? 'Авторизоваться заново' : 'Авторизовать ВК' }}</a>
        </div>
    </div>
@endsection
