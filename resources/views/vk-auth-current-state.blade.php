@extends('layout')

@section('content')
    <div class="row">
        <div class="col-md-6 form-group">
            @if ($connection)
                @if (isset($connection->data['account_id']))
                    <p>ВК подключен</p>
                    <p>Выбран {{$connection->data['account_name']}} #{{$connection->data['account_id']}}</p>
                @else
                    <div class="alert alert-warning">
                        <p>При подключении ВК аккаунт не был выбран. Требуется повторить авторизацию.</p>
                    </div>
                @endif
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
