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
                <td>
                    <a href="https://docs.google.com/spreadsheets/d/{{ $export['spreadsheetId'] }}" target="_blank">{{ $export['spreadsheetId'] }}</a>
                    @if(!in_array($export['status'], ['pending', 'done']))
                        <div class="my-2">
                            <a href="/exports_confirm?sid={{ $export['spreadsheetId'] }}">
                                <button type="button" class="btn btn-outline-secondary btn-sm">повторить</button>
                            </a>
                        </div>
                    @endif
                </td>
                <td>{{ $export['created_at'] }}</td>
                <td>{{ $export['updated_at'] }}</td>
                <td class="text-center table-{{ ['done' => 'success', 'done_with_errors' => 'warning', 'failed' => 'danger', 'interrupted' => 'warning'][$export['status']] ?? 'info'}}">{{ $export['status'] }}</td>
            </tr>
            @endforeach

            </tbody>
        </table>
    </div>
</div>
@endsection
