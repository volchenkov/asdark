@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-8">
        <p>Для продолжения выберите кампании, объявлений которых хотите редактировать. Пока доступно редактирование до 2000 объявлений (до 100 для объявлений с постами).</p>
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
            <label for="campaign_ids">Кампании:</label>
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
            <div class="text-muted small">Ctrl+F для поиска, зажать Сtrl для выбора нескольких</div>
            @endif
        </div>
    </div>
    <div class="row ">
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox"
                       name="need_posts"
                       id="need_posts"
                       class="form-check-input" />
                <label for="need_posts" class="form-check-label">добавить поля постов</label>
            </div>
            <div class="text-muted small">Отметьте, если нужно редактировать поля рекламного поста, привязанного к объявлению</div>
        </div>
    </div>

    <div class="row mb-5 mt-3">
        <div class="col-md-8">
            <p class="small text-muted">Будет сгенерирована Google таблица с текущим состоянием объявлений из выбранных кампаний. Ее можно будет поправить и загрузить в ВК. <a href="/help#editable-fields" target="_blank">Здесь</a> описание редактируемых полей.</p>
            <input class="btn btn-primary" type="submit" />
        </div>
    </div>
</form>
@endsection
