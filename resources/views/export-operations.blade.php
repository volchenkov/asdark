@extends('export')

@section('export-tab')

@php
    $statuses = [
        \App\ExportOperation::STATUS_PENDING    => ['title' => 'в ожидании', 'color' => 'muted', 'icon' => '&#128164;'],
        \App\ExportOperation::STATUS_PROCESSING => ['title' => 'в работе', 'color' => 'default', 'icon' => '&#9203;'],
        \App\ExportOperation::STATUS_FAILED     => ['title' => 'ошибка', 'color' => 'danger', 'icon' => '&#10060;'],
        \App\ExportOperation::STATUS_ABORTED    => ['title' => 'прекращена', 'color' => 'default', 'icon' => '&#9888;'],
        \App\ExportOperation::STATUS_DONE       => ['title' => 'готова', 'color' => 'success', 'icon' => '&#10004;'],
    ];
    $types = [
        \App\ExportOperation::TYPE_UPDATE_AD   => 'обновление полей',
        \App\ExportOperation::TYPE_UPDATE_POST => 'обновление поста',
        \App\ExportOperation::TYPE_UPDATE_CARD => 'обновление карточки карусели',
    ];
@endphp

<ul class="list-group list-group-flush small">
    @foreach($export->operations->sortBy('ad_id')->groupBy('ad_id') as $adId => $operations)
        @foreach($operations as $op)
            <li class="list-group-item text-{{ $statuses[$op->status]['color'] }}">
                <span class="mr-3" title="{{ $statuses[$op->status]['title'] }}"> {!! $statuses[$op->status]['icon'] !!} </span>
                <span class="mr-3" title="ID объявления">{{$adId}}</span>
                <span>{{ $types[$op->type] }}: </span>
                @foreach($op->state_to as $field => $newValue)
                    <span title="{{ $op->state_from[$field] }} -> {{ $newValue }}" class="mr-1">{{ $field }}</span>
                @endforeach
            </li>
        @endforeach
    @endforeach
</ul>
@endsection
