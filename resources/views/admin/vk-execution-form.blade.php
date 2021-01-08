@extends('layout')

@section('content')
    <div class="row">
        <div class="col">
            <form action="/admin/vk_execute" method="POST">
                @csrf
                <div class="form-group">
                    <label for="code">Код алгоритма:</label>
                    <textarea class="form-control"
                              name="code"
                              id="code"
                              rows="7"
                              required>{{ $code }}</textarea>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit">отправить</button>
                </div>
            </form>

            @if($response)
            <div class="form-group">
                <pre><code>@json($response, JSON_PRETTY_PRINT)</code></pre>
            </div>
            @endif
        </div>
    </div>
@endsection
