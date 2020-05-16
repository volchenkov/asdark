@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-8">
        <p>После отправки формы будет сгенерирована Google таблица, которую можно будет поправить и отправить на загрузку в ВК.</p>
        <p>Доступно редактирование до 2000 объявлений (100 для объявлений с постами).</p>
    </div>
</div>
<form action="/ads_edit_generate">
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="campaign_ids">Кампании</label>
            <select name="campaign_ids[]"
                    id="campaign_ids"
                    class="form-control"
                    multiple
                    required>
                @foreach ($campaigns as $campaign)
                    <option value="{{ $campaign['id'] }}"> {{ $campaign['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="ad_fields">Поля объявлений для редактирования.</label>
            <select name="ad_fields[]"
                    id="ad_fields"
                    class="form-control"
                    multiple
                    required>
                <option value="campaign_id" disabled> ID кампании (campaign_id)</option>
                <option value="ad_id" disabled> ID объявления (ad_id) </option>
                @foreach ($editableFields as $id => $desc)
                    <option value="{{ $id }}"> {{ $desc }} ({{ $id }})</option>
                @endforeach
            </select>
            <p><small>Отмеченные серым добавляются всегда.</small></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <input class="btn btn-primary" type="submit" />
        </div>
    </div>
</form>
@endsection
