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
    ];

    $operationStatuses = [
        \App\ExportOperation::STATUS_DONE       => ['title' => 'Готова', 'color' => 'success'],
        \App\ExportOperation::STATUS_PENDING    => ['title' => 'В ожидании', 'color' => 'info'],
        \App\ExportOperation::STATUS_FAILED     => ['title' => 'Ошибка', 'color' => 'danger'],
        \App\ExportOperation::STATUS_ABORTED    => ['title' => 'Прекращена', 'color' => 'warning'],
        \App\ExportOperation::STATUS_PROCESSING => ['title' => 'В работе', 'color' => 'info'],
    ];
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
    @if($export['captcha_code'])
        <div class="col-md-2">
            <div class="small text-muted">Капча</div>
            {{ $export['captcha_code'] }}
        </div>
    @endif
</div>

@if($export['status'] !== \App\Export::STATUS_PROCESSING)
    <div class="row mb-4">
        <div class="col-12">
            @if($export['status'] == \App\Export::STATUS_INTERRUPTED)
                <a href="exports_captcha?export_id={{ $export['id'] }}" class="btn btn-success btn-sm mr-1" role="button">ввести капчу</a>
            @else
                @if(!in_array($export['status'], [\App\Export::STATUS_PENDING]))
                    <a href="/exports_confirm?sid={{ $export['sid'] }}" class="btn btn-primary btn-sm mr-1" role="button">
                        повторить загрузку
                    </a>
                @endif

                @if(in_array($export['status'], [\App\Export::STATUS_PENDING]))
                    <a href="/exports_cancel?id={{ $export['id'] }}" class="btn btn-secondary btn-sm mr-1" role="button">
                        отменить загрузку
                    </a>
                @endif
            @endif
        </div>
    </div>
@endif

<div class="row mb-4">
    <div class="col-12">
        @if($export->operations->count() > 0)
            <div class="my-2">
                <a href="/exports_operations?export_id={{ $export->id }}">Операций: {{ $export->operations->count() }}</a>
            </div>
        @endif

        @php
            $logColors = [
                \App\ExportLog::LEVEL_ERROR   => 'danger',
                \App\ExportLog::LEVEL_WARNING => 'warning',
                \App\ExportLog::LEVEL_NOTICE  => 'dark',
                \App\ExportLog::LEVEL_INFO    => 'secondary',
            ];
        @endphp
        @foreach($logs->sortBy('id') as $log)
            <div>
                <span class="text-black-50 small">{{ $log->created_at }}</span>
                <span class="text-{{ $logColors[$log->level] }}"> {{ $log->message }} </span>
            </div>
        @endforeach
    </div>
</div>

@if($export['status'] == 'failed')
    <div class="row mb-2">
        <div class="col-12 alert alert-warning">
            <h5>Произошла ошибка</h5>
            <p>{{ $export['failure'] }}</p>
        </div>
    </div>
@endif

@if(in_array($export['status'], [\App\Export::STATUS_PROCESSING]))
    <div class="row mb-4">
        <div class="col-12 text-center">
            <div class="spinner-border text-secondary" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
        </div>
    </div>
@endif

@endsection
