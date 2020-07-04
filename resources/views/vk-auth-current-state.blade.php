@extends('layout')

@section('content')
    <div class="row">
        <div class="col-md-6 form-group">
            @if ($connection)
                @if (isset($connection->data['account_id']))
                    <p> &#128076; <strong class="text-success">ВК подключен</strong></p>
                    <div>Выбран {{$connection->data['account_name']}} #{{$connection->data['account_id']}}</div>
                    <p class="small text-muted">Обновлено {{ $connection->updated_at->addHours(3) }}</p>
                @else
                    <div class="alert alert-warning">
                        При подключении ВК аккаунт не был выбран. Требуется повторить авторизацию.
                    </div>
                @endif
            @else
                <p>ВК пока не подключен</p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <a href="/vk_auth" class="btn btn-primary" tabindex="-1" role="button" aria-disabled="true">{{ $connection ? 'подключить заново' : 'подключить' }}</a>
        </div>
    </div>
@endsection
