@extends('layout')

@section('content')
    <div class="row">
        <div class="col-8">
            <h3><a name="editable-fields"></a>Список редактируемых полей</h3>
            <ul>
                @foreach($fields as $name => $field)
                    <li> <strong>{{ $name }}</strong> - {{ $field['desc'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
