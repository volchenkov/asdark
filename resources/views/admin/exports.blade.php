@extends('layout')

@section('content')

<div class="row">
    <div class="col-md-12">
        <h2> Загрузки</h2>
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">Создана</th>
                <th scope="col">Автор</th>
                <th scope="col">Клиент</th>
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
                <td>{{ $export['client_name'] ?: 'Клиент по умолчанию' }}</td>
                <td title="{{ $export['updated_at'] ? 'Обновлена '.$export['updated_at']->addHours(3) : ''  }}">
                    @php
                        $statuses = [
                            'pending'          => ['color' => 'info', 'title' => 'Ожидает', 'textClass' => 'text-dark'],
                            'processing'       => ['color' => 'info', 'title' => 'В работе', 'textClass' => 'text-dark'],
                            'done'             => ['color' => 'success', 'title' => 'Готова'],
                            'done_with_errors' => ['color' => 'warning', 'title' => 'Завершена с ошибками', 'textClass' => 'text-dark'],
                            'failed'           => ['color' => 'danger', 'title' => 'Провалена'],
                            'interrupted'      => ['color' => 'warning', 'title' => 'Требуется капча', 'textClass' => 'text-dark'],
                            'canceled'         => ['color' => 'light', 'title' => 'Отменена', 'textClass' => 'text-dark']
                        ]
                    @endphp
                    <span class="px-2 py-1 badge bg-{{ $statuses[$export['status']]['color'] ?? 'default'}} {{ $statuses[$export['status']]['textClass'] ?? ''}}"> {{ $statuses[$export['status']]['title'] ?? $export['status'] }}</span>
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
