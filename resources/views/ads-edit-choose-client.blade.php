@extends('layout')

@section('content')
<form action="/ads_edit_form">
    <div class="row">
        <div class="col-md-8 form-group">
            <label for="client_id">Выберите клиента</label>
            <select name="client_id"
                    id="client_id"
                    class="form-control"
                    size="{{ count($clients) > 30 ? 30 : count($clients) }}"
                    required>
                @foreach ($clients as $client)
                    <option value="{{ $client['id'] }}"> {{ $client['name'] }}</option>
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