@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Spreadsheet</th>
                <th scope="col">Создана</th>
                <th scope="col">Последняя загрузка</th>
                <th scope="col">Статус</th>
            </tr>
            </thead>
            <tbody>
            @foreach($exports as $export)
            <tr>
                <td><a href="https://docs.google.com/spreadsheets/d/{{ $export['spreadsheetId'] }}" target="_blank">{{ $export['spreadsheetId'] }}</a></td>
                <td>{{ $export['created_at'] }}</td>
                <td>{{ $export['updated_at'] }}</td>
                <td class="text-center table-{{ ['done' => 'success'][$export['status']] ?? 'danger'}}">{{ $export['status'] }}</td>
            </tr>
            @endforeach

            </tbody>
        </table>
    </div>
</div>
@endsection
