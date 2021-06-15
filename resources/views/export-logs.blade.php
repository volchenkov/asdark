@extends('export')

@section('export-tab')
    @php
        $logClasses = [
            \App\ExportLog::LEVEL_ERROR   => 'text-danger',
            \App\ExportLog::LEVEL_WARNING => 'font-weight-bold',
            \App\ExportLog::LEVEL_NOTICE  => 'font-weight-bold',
            \App\ExportLog::LEVEL_INFO    => '',
        ];
    @endphp

    @foreach($export->logs->sortBy('id') as $log)
        <div>
            <span class="text-muted small"
                  title="Время события {{ $log->created_at->addHours(3) }}">{{ $log->created_at->addHours(3)->format('H:i:s') }}</span>
            <span class="{{ $logClasses[$log->level] }} ms-2">{{ $log->message }}</span>
        </div>
    @endforeach

    @if(in_array($export['status'], [\App\Export::STATUS_PROCESSING]))
        <div class="text-center my-3" title="Автоматическое обновление каждые 10 секунд">
            <div class="spinner-border text-secondary" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
        </div>
    @endif
@endsection
