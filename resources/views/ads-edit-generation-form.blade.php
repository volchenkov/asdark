@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-8">
    </div>
</div>
<script type="text/javascript">
    function disableSubmitButton(event, form) {
        let btn = form.querySelector('button[type=submit]');
        btn.disabled = true;

        let btnLoader = btn.querySelector('span.submit-loader');
        btnLoader.style.display = 'inline-block';

        let btnTitle = btn.querySelector('span.submit-title');
        btnTitle.innerText = 'создается таблица';
    }
</script>
<form action="/ads_edit_generate" onsubmit="return disableSubmitButton(event, this);">
    @if ($clientId)
        <input type="hidden" name="client_id" value="{{ $clientId }}"/>
    @endif

    <div class="row">
        <div class="col-md-8 form-group">
            @if (!$campaigns)
                <p><strong>Не найдено ни одной кампании клиента.</strong></p>
            @else
            <label for="campaign_ids">Выберите кампании:</label>
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
            <div class="text-muted small"><strong>Ctrl + F</strong> для поиска, <strong>удерживать Сtrl</strong> для выбора нескольких</div>
            <p class="text-muted small">Ознакомьтесь с <a href="/help#caveats">ограничениями редактирования</a> и <a href="/help#editable-fields" target="_blank">списком редактируемых полей</a></p>
            @endif
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-8">
            <button class="btn btn-primary" type="submit" id="feed-generation-submit">
                <span style="display: none"
                      class="submit-loader spinner-border spinner-border-sm mr-1"
                      role="status"
                      aria-hidden="true"></span>
                <span class="submit-title">далее</span>
            </button>
        </div>
    </div>
</form>
@endsection
