@extends('layout')

@section('content')

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
<div class="row mb-2">
    <div class="col-12">
        <h2>Загрузка #{{ $export['id'] }}</h2>
    </div>
</div>

<div class="row mb-2 bg-light">
    <div class="col-md-3">
        <div class="small text-muted">Статус</div>
        <span class="px-2 text-{{ $statuses[$export['status']]['color'] ?? 'default'}} border border-{{ $statuses[$export['status']]['color'] ?? 'default'}}">{{ $statuses[$export['status']]['title'] ?? $export['status'] }}</span>
    </div>
    <div class="col-md-6">
        <div class="small text-muted">Таблица</div>
        <a href="https://docs.google.com/spreadsheets/d/{{ $export['sid'] }}" target="_blank"> {{ $export['sid'] }} </a>
    </div>
</div>
<div class="row mb-4 bg-light">
    <div class="col-md-3">
        <div class="small text-muted">Автор</div>
        {{ $export['user']->name }}
    </div>
    <div class="col-md-2">
        <div class="small text-muted">Создана</div>
        {{ $export['created_at']->addHours(3) }}
    </div>
    <div class="col-md-2">
        <div class="small text-muted">Обновлена</div>
        {{ $export['updated_at']->addHours(3) }}
    </div>
</div>

@if($export['status'] !== \App\Export::STATUS_PROCESSING)
    <div class="row mb-4">
        <div class="col-12">
            @if(!in_array($export['status'], [\App\Export::STATUS_PENDING]))
                <a href="/exports_confirm?sid={{ $export['sid'] }}" class="btn btn-primary btn-sm mr-2" role="button">
                    повторить загрузку
                </a>
            @endif
            @if(in_array($export['status'], [\App\Export::STATUS_PENDING]))
                <a href="/exports_cancel?id={{ $export['id'] }}" class="btn btn-secondary btn-sm mr-2" role="button">
                    отменить загрузку
                </a>
            @endif
        </div>
    </div>
@endif

@if($export['status'] == 'failed')
<div class="row mb-4">
    <div class="col-8">
        <h4>Ошибка выполнения</h4>
        <p class="text-danger">{{ $export['failure'] }}</p>
    </div>
</div>
@endif

@if ($logs->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <h4>Логи</h4>
        @foreach($logs->sortBy('id') as $log)
            <div>
                <span class="text-black-50 small">{{ $log->created_at }}</span>
                <span class="text-{{ $log->level == 'info' ? 'default' : $log->level }}"></span> {{ $log->message }}
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
