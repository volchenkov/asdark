@extends('layout')

@section('content')
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
<div class="row mb-2">
    <div class="col-12">
        <h2>Загрузка #{{ $export['id'] }}</h2>
    </div>
</div>

<div class="row mb-2">
    <div class="col-md-3">
        <div class="small text-muted">Автор</div>
        {{ $export['user']->name }}
    </div>
    <div class="col-md-3">
        <div class="small text-muted">Клиент</div>
        {{ $export['client_name'] ?: 'Клиент по умолчанию' }}
    </div>
    <div class="col-md-6">
        <div class="small text-muted">Таблица</div>
        <a href="https://docs.google.com/spreadsheets/d/{{ $export['sid'] }}" target="_blank"> {{ $export['sid'] }} </a>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small text-muted">Статус</div>
        <span class="px-2 py-1 badge bg-{{ $statuses[$export['status']]['color'] ?? 'default'}} {{ $statuses[$export['status']]['textClass'] ?? ''}}"> {{ $statuses[$export['status']]['title'] ?? $export['status'] }}</span>
    </div>
    <div class="col-md-3">
        <div class="small text-muted">Создана</div>
        {{ $export['created_at']->addHours(3) }}
    </div>
    <div class="col-md-3">
        <div class="small text-muted">Обновлена</div>
        {{ $export['updated_at']->addHours(3) }}
    </div>
    @if($export['captcha_code'])
        <div class="col-3">
            <div class="small text-muted">Капча</div>
            {{ $export['captcha_code'] }}
        </div>
    @endif

    <div class="col-12 mt-3">
        @if(in_array($export['status'], [\App\Export::STATUS_FAILED, \App\Export::STATUS_PARTIAL_FAILURE]))
            <a href="{{ route('export.rerun', ['id' => $export['id']]) }}"
               class="btn btn-primary btn-sm me-1 mt-3"
               role="button">
                повторить загрузку
            </a>
        @endif

        @if(in_array($export['status'], [\App\Export::STATUS_INTERRUPTED]))
            <a href="{{ route('export.captcha', ['export_id' => $export['id']]) }}"
               class="btn btn-success btn-sm me-1 mt-3"
               role="button">
                ввести капчу
            </a>
        @endif

        @if(in_array($export['status'], [\App\Export::STATUS_PENDING]))
            <a href="{{ route('export.cancel', ['id' => $export['id']]) }}"
               class="btn btn-secondary btn-sm me-1 mt-3"
               role="button">
                отменить загрузку
            </a>
        @endif
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


<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs">
            @if($export->logs->count() > 0)
                <li class="nav-item">
                    <a class="nav-link{{ request()->routeIs('export.logs') ? ' active' : '' }}"
                       href="{{ route('export.logs', ['export_id' => $export->id]) }}">Логи</a>
                </li>
            @endif

            @if($export->operations->count() > 0)
                <li class="nav-item">
                    <a class="nav-link{{ request()->routeIs('export.operations') ? ' active' : '' }}"
                       href="{{ route('export.operations', ['export_id' => $export->id]) }}">Операции [ {{ $export->operations->where('status', \App\ExportOperation::STATUS_DONE)->count() }}  / {{ $export->operations->count() }} ]</a>
                </li>
            @endif
        </ul>

        <div class="my-2">
            @yield('export-tab')
        </div>

        @if($export['status'] == \App\Export::STATUS_PENDING)
            <div class="my-2">
                <p>Загрузка пока в очереди, скоро начнется</p>
            </div>
        @endif
        @if($export['status'] == \App\Export::STATUS_CANCELED)
            <div class="my-2">
                <p>Загрузка была отменена, с этим уже ничего не поделать</p>
            </div>
        @endif
    </div>
</div>
@endsection
