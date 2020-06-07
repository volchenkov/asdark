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
                    <div>
                        <a href="https://docs.google.com/spreadsheets/d/{{ $export['spreadsheetId'] }}" target="_blank">{{ $export['spreadsheetId'] }}</a>
                    </div>
                    @if(!in_array($export['status'], ['pending', 'processing', 'done']))
                        <a href="/exports_confirm?sid={{ $export['spreadsheetId'] }}" class="mr-3 my-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm">повторить</button>
                        </a>
                    @endif
                    @if(!in_array($export['status'], ['pending']))
                        <a href="/exports_logs?sid={{ $export['spreadsheetId'] }}" target="_blank" class="mr-1 my-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm">логи</button>
                        </a>
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
