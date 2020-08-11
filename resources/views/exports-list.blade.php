@extends('layout')

@section('content')

<div class="row mb-2">
    <div class="col">
        @if ($vkConnection && isset($vkConnection->data['account_id']))
            <div class="py-4 text-center">
                <a href="{{ route('adsEdit.start') }}" class="btn btn-primary" role="button">редактировать объявления</a>
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">Необходимо подключить ВК</h4>
                <p>Для редактирования объявлений нужно <a href="{{ route('vkAuth.state') }}" class="alert-link">авторизовать приложение в ВК</a></p>
            </div>
        @endif
    </div>
</div>


<div class="row">
    <div class="col-md-12">
        <h2> Загрузки</h2>
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">Создана</th>
                <th scope="col">Автор</th>
                <th scope="col">Таблица объявлений</th>
                <th scope="col">Статус</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($exports as $export)
            <tr>
                <td>
                    {{ $export['created_at']->addHours(3)->format('H:i:s') }}
                    <div class="small text-muted">{{ $export['created_at']->format('Y-m-d') }}</div>
                </td>
                <td>{{ $export['user']->name }}</td>
                <td>
                    <div>
                        <a href="https://docs.google.com/spreadsheets/d/{{ $export['sid'] }}" target="_blank">{{ $export['sid'] }}</a>
                    </div>
                </td>
                <td title="{{ $export['updated_at'] ? 'Обновлена '.$export['updated_at']->addHours(3) : ''  }}">
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
                </td>
                <td>
                    <a href="/export?export_id={{ $export['id'] }}" class="btn btn-light btn-sm" role="button">
                        подробнее
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>

        @if($exports->count() > 50)
            <p class="text-center text-muted small">
                Представлены последние 50 загрузок<br/> Архив доступен по запросу
            </p>
        @endif
    </div>
</div>
@endsection
