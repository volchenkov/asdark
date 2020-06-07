@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-8">
        <p>После отправки формы будет сгенерирована Google таблица, которую можно будет поправить и отправить на загрузку в ВК.</p>
        <p>Пока доступно редактирование до 2000 объявлений (100 для объявлений с постами).</p>
    </div>
</div>
<form action="/ads_edit_generate">
    @if ($clientId)
        <input type="hidden" name="client_id" value="{{ $clientId }}"/>
    @endif

    <div class="row">
        <div class="col-md-8 form-group">
            @if (!$campaigns)
                <p><strong>Не найдено ни одной кампании клиента.</strong></p>
            @else
            <label for="campaign_ids">Кампании</label>
            <select name="campaign_ids[]"
                    id="campaign_ids"
                    class="form-control"
                    multiple
                    size="{{ count($campaigns) }}"
                    required>
                @foreach ($campaigns as $campaign)
                    <option value="{{ $campaign['id'] }}"> {{ $campaign['name'] }}</option>
                @endforeach
            </select>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="ad_fields">Поля объявлений для редактирования. В скобках - название поля в загрузке.</label>
            <select name="ad_fields[]"
                    id="ad_fields"
                    class="form-control"
                    multiple
                    size="{{ count($fields) }}"
                    required>
                @foreach ($fields as $id => $field)
                    <option value="{{ $id }}"> {{ $field['desc'] }} ({{ $id }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <input class="btn btn-primary" type="submit" />
        </div>
    </div>
</form>
@endsection
