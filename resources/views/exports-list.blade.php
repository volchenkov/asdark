@extends('layout')

@section('content')
<div class="row">
    <div class="col-md-12">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Создана</th>
                <th scope="col">Google таблица</th>
                <th scope="col">Статус</th>
            </tr>
            </thead>
            <tbody>
            @foreach($exports as $export)
            <tr>
                <td>
                    {{ $export['created_at']->format('H:i:s') }}
                    <div class="small text-muted">{{ $export['created_at']->format('Y-m-d') }}</div>
                </td>
                <td>
                    <div>
                        <a href="https://docs.google.com/spreadsheets/d/{{ $export['sid'] }}" target="_blank">{{ $export['sid'] }}</a>
                    </div>

                </td>
                <td title="{{ $export['updated_at'] ? 'Обновлена '.$export['updated_at'] : ''  }}">
                    @php
                        $statuses = [
                            'pending'          => ['color' => 'info', 'title' => 'Ожидает'],
                            'processing'       => ['color' => 'info', 'title' => 'В работе'],
                            'done'             => ['color' => 'success', 'title' => 'Готова'],
                            'done_with_errors' => ['color' => 'warning', 'title' => 'Завершена с ошибками'],
                            'failed'           => ['color' => 'danger', 'title' => 'Провалена'],
                            'interrupted'      => ['color' => 'warning', 'title' => 'Требуется капча'],
                            'canceled'         => ['color' => 'muted', 'title' => 'Отменена']
                        ]
                    @endphp
                    <span class="px-2 text-{{ $statuses[$export['status']]['color'] ?? 'default'}} border border-{{ $statuses[$export['status']]['color'] ?? 'default'}}"> {{ $statuses[$export['status']]['title'] ?? $export['status'] }}</span>
                    @if($export['status'] == 'failed')
                        <details class="small">
                            <summary>Об ошибке</summary>
                            {{ $export['failure'] }}
                        </details>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            действия
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @if(!in_array($export['status'], ['pending', 'done', 'processing']))
                                <a href="/exports_confirm?sid={{ $export['sid'] }}" class="dropdown-item">
                                    повторить
                                </a>
                            @endif
                            @if(in_array($export['status'], ['pending']))
                                <a href="/exports_cancel?id={{ $export['id'] }}" class="dropdown-item">
                                    отменить
                                </a>
                            @endif
                            @if(!in_array($export['status'], ['pending']))
                                <a href="/exports_logs?sid={{ $export['sid'] }}" target="_blank" class="dropdown-item">
                                    логи
                                </a>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach

            </tbody>
        </table>
    </div>
</div>
@endsection
