@extends('export')

@section('export-tab')
    @php
        $logColors = [
            \App\ExportLog::LEVEL_ERROR   => 'danger',
            \App\ExportLog::LEVEL_WARNING => 'dark',
            \App\ExportLog::LEVEL_NOTICE  => 'dark',
            \App\ExportLog::LEVEL_INFO    => 'secondary',
        ];
    @endphp

    @foreach($export->logs->sortBy('id') as $log)
        <div>
            <span class="text-black-50 small">{{ $log->created_at }}</span>
            <span class="text-{{ $logColors[$log->level] }}"> {{ $log->message }} </span>
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
