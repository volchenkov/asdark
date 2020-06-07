@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if ($ok)
            <form action="/vk_auth_save" method="POST">
                <input type="hidden" name="access_token" value="{{ $accessToken }}"/>
                <input type="hidden" name="expires_in" value="{{ $accessIn }}"/>
                <input type="hidden" name="vk_user_id" value="{{ $userId }}"/>

                <div class="row">
                    <div class="col-md-8 form-group">
                        <label for="account_id">Кампании</label>
                        <input  class="form-control"
                                name="account_id"
                                type="text"
                                placeholder="ID рекламного кабинета"
                                required
                                id="account_id"/>
                    </div>
                </div>
                <div class="row mb-5 mt-3">
                    <div class="col-12">
                        <input class="btn btn-primary" type="submit" />
                    </div>
                </div>
            </form>
        @else
            <p>Произошла ошибка. Пожалуйста, предоставьте информацию ниже администратору сайта.</p>
            <div class="alert alert-warning" role="alert">
                {{ $vkResponse }}
            </div>
        @endif
    </div>
</div>
@endsection
