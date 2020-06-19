@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        <form action="/vk_auth_save" method="POST">
            @csrf
            <input type="hidden" name="accounts" value="{{ json_encode($accounts) }}">
            <div class="row">
                <div class="col-md-8 form-group">
                    <label for="account_id">Для продолжения выберите рекламный кабинет:</label>
                    <select class="form-control"
                            name="account_id"
                            required
                            id="account_id">
                        @foreach($accounts as $account)
                            <option value="{{ $account['account_id'] }}"> {{ $account['account_name'] }} ({{ $account['account_id'] }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-5 mt-3">
                <div class="col-12">
                    <input class="btn btn-primary" type="submit" value="Сохранить" />
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
