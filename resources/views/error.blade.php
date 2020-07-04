@extends('layout')

@section('content')
    <div class="row">
        <div class="col-12">
            <h3>Что-то пошло не так 😞</h3>
            @if(isset($message))
                <div class="alert alert-warning"> {{ $message }}</div>
            @endif
            @if(isset($todo))
                <p> {{ $todo }}</p>
            @endif
            @if(isset($details))
                <details class="small text-muted">
                    <summary>Информация для разработчика</summary>
                    {{ $details }}
                </details>
            @endif
        </div>
    </div>
@endsection
