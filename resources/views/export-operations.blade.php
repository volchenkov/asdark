@extends('export')

@section('export-tab')

@php
    $statuses = [
        \App\ExportOperation::STATUS_PENDING    => ['title' => 'в ожидании', 'color' => 'muted', 'icon' => '&#128164;'],
        \App\ExportOperation::STATUS_PROCESSING => ['title' => 'в работе', 'color' => 'default', 'icon' => '&#9203;'],
        \App\ExportOperation::STATUS_FAILED     => ['title' => 'прервана с ошибкой', 'color' => 'danger', 'icon' => '&#10060;'],
        \App\ExportOperation::STATUS_ABORTED    => ['title' => 'прекращена', 'color' => 'default', 'icon' => '&#9888;'],
        \App\ExportOperation::STATUS_DONE       => ['title' => 'исполнена', 'color' => 'success', 'icon' => '&#10004;'],
    ];
    $types = [
        \App\ExportOperation::TYPE_UPDATE_AD   => 'обновление полей',
        \App\ExportOperation::TYPE_UPDATE_POST => 'обновление поста',
        \App\ExportOperation::TYPE_UPDATE_CARD => 'обновление карточки карусели',
    ];
@endphp

<table class="table table-borderless table-sm">
    <tr>
        <th>Объявление</th>
        <th></th>
    </tr>
    @foreach($export->operations->sortBy('ad_id')->groupBy('ad_id') as $adId => $operations)
        <tbody class="{{ !$loop->last ? 'border-bottom' : '' }}">
        @foreach($operations as $op)
            <tr>
                @if ($loop->first)
                <td rowspan="{{ $operations->count() }}"> {{ $adId }}</td>
                @endif
                <td>
                    <details>
                        <summary class="text-{{ $statuses[$op->status]['color'] }}">
                            <span>{{ $types[$op->type] }}: </span>
                            @foreach($op->state_to as $field => $newValue)
                                <span title="{{ $op->state_from[$field] }} -> {{ $newValue }}" class="mr-1">{{ $field }}</span>
                            @endforeach

                            <button class="btn ml-1">
                                <svg class="fill-current w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M12.95 10.707l.707-.707L8 4.343 6.586 5.757 10.828 10l-4.242 4.243L8 15.657l4.95-4.95z"/>
                                </svg>
                            </button>
                        </summary>

                        <div class="px-3 py-2">
                            <div class="mb-2">
                                <span title="">{!! $statuses[$op->status]['icon'] !!} операция {{ $statuses[$op->status]['title'] }}</span>
                            </div>
                            @foreach($op->state_to as $field => $newValue)
                                <div class="{{ !$loop->first ? 'mt-3' : ''}}">Поле <strong>{{ $field }}</strong>:</div>
                                <div class="text-muted small">До</div>
                                <div>{{ $op->state_from[$field] }}</div>

                                <div class="mt-1 text-muted small">После</div>
                                <div>{{ $newValue }}</div>
                            @endforeach
                        </div>
                    </details>
                </td>
            </tr>
        @endforeach
        </tbody>
    @endforeach
</table>
@endsection
