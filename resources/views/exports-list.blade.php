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
                        <a href="https://docs.google.com/spreadsheets/d/{{ $export['sid'] }}" target="_blank">{{ $export['sid'] }}</a>
                    </div>
                    @if(!in_array($export['status'], ['pending', 'done', 'processing']))
                        <a href="/exports_confirm?sid={{ $export['sid'] }}" class="mr-1 my-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm">повторить</button>
                        </a>
                    @endif
                    @if(in_array($export['status'], ['pending']))
                        <a href="/exports_cancel?id={{ $export['id'] }}" class="mr-1 my-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm">отменить</button>
                        </a>
                    @endif
                    @if(!in_array($export['status'], ['pending']))
                        <a href="/exports_logs?sid={{ $export['sid'] }}" target="_blank" class="mr-1 my-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm">логи</button>
                        </a>
                    @endif
                </td>
                <td>{{ $export['created_at'] }}</td>
                <td>{{ $export['updated_at'] }}</td>
                <td class="text-center table-{{ ['pending' => 'info', 'processing' => 'info', 'done' => 'success', 'done_with_errors' => 'warning', 'failed' => 'danger', 'interrupted' => 'warning'][$export['status']] ?? 'default'}}">{{ $export['status'] }}</td>
            </tr>
            @endforeach

            </tbody>
        </table>
    </div>
</div>
@endsection
