@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-8">
        <p>После отправки формы будет сгенерирована Google таблица, которую можно будет поправить и отправить на загрузку в ВК.</p>
    </div>
</div>
<form action="/cds_generate">
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="promo_name">Название акции</label>
            <input name="promo_name"
                   id="promo_name"
                   type="text"
                   placeholder="Название акции"
                   class="form-control"
                   required/>
        </div>
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
        <div class="col-md-8 form-group">
            <label for="promo_name">Spreadsheet ID таргетингов</label>
            <input name="targetings_sid"
                   id="targetings_sid"
                   type="text"
                   placeholder="Spreadsheet ID"
                   class="form-control"
                   required/>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8"><hr/></div>
    </div>
    <div class="row">
        <div class="col-md-4 form-group">
            <label for="ad_format">Формат объявления</label>
            <select name="ad_format"
                    id="ad_format"
                    class="form-control"
                    required>
                <option value="9">Запись в сообществе</option>
            </select>
        </div>
        <div class="col-md-4 form-group">
            <label for="category1_id">Тематика объявления</label>
            <select name="category1_id"
                    id="category1_id"
                    class="form-control"
                    required>
                <option value="357">Недвижимость</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="ad_name">Имя объявления</label>
            <input name="ad_name"
                   id="ad_name"
                   type="text"
                   placeholder="Имя объявления"
                   title="Доступные замены: {promo}, {targeting_name}"
                   class="form-control"
                   required
                   value="{promo}_{targeting_name} объявление" />
        </div>
        <div class="col-md-8 form-group">
            <label for="creative">Ссылка на креатив для промопоста</label>
            <input name="creative"
                   id="creative"
                   type="text"
                   placeholder="http://sample.com/1.jpg"
                   class="form-control"
                   required/>
        </div>
        <div class="col-md-8 form-group">
            <label for="message">Текст рекламного поста</label>
            <textarea name="message"
                      id="message"
                      class="form-control"
                      required></textarea>
        </div>
        <div class="col-md-8 form-group">
            <label for="form_uri">Ссылка на форму заявок</label>
            <input name="form_uri"
                   type="text"
                   placeholder="https://vk.com/app11111_-22222?form_id=1#form_id=1"
                   required
                   class="form-control"
                   value="https://vk.com/app6013442_-193429649?form_id=1#form_id=1" />
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 form-group">
            <label for="link_button">Текст кнопки перехода</label>
            <select name="link_button"
                    id="link_button"
                    class="form-control"
                    required>
                <option value="auto">Автоматически</option>
            </select>
        </div>
        <div class="col-md-4 form-group">
            <label for="post_owner_id">ID группы вк (с минусом)</label>
            <input type="text"
                   name="post_owner_id"
                   id="post_owner_id"
                   class="form-control"
                   placeholder="-1111111"
                   value="-193429649"
                   required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8"><hr/></div>
    </div>

    <div class="row">
        <div class="col-md-2 form-group">
            <label for="goal_type">Цель</label>
            <select name="goal_type"
                    id="goal_type"
                    class="form-control"
                    required>
                <option value="3">Заявки</option>
            </select>
        </div>
        <div class="col-md-2 form-group">
            <label for="cost_type">Способ оплаты</label>
            <select name="cost_type"
                    id="cost_type"
                    class="form-control"
                    required>
                <option value="3">За показы, оптимизированный</option>
            </select>
        </div>
        <div class="col-md-2 form-group">
            <label for="ocpm">oCPM, руб.</label>
            <input name="ocpm"
                   id="ocpm"
                   type="number"
                   class="form-control"
                   title="Желаемая стоимость заявки"
                   placeholder="OCPM"
                   value="300" />
        </div>
        <div class="col-md-2 form-group">
            <label for="day_limit">Дневной лимит, руб.</label>
            <input name="day_limit"
                   id="day_limit"
                   type="number"
                   class="form-control"
                   placeholder="Дневной лимит"
                   value="300" />
        </div>
{{--        <div class="col-12">--}}
{{--            <div class="form-check" title="Изменение этой настройки сейчас не поддерживается">--}}
{{--                <input type="checkbox"--}}
{{--                       name="autobidding"--}}
{{--                       id="autobidding"--}}
{{--                       disabled--}}
{{--                       class="form-check-input" />--}}
{{--                <label for="autobidding" class="form-check-label">Автоуправление ценой</label>--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>
    <div class="row mt-4 mb-5">
        <div class="col-12">
            <input class="btn btn-primary" type="submit" />
        </div>
    </div>
</form>
@endsection
