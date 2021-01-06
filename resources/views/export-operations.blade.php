@extends('export')

@section('export-tab')

@php
    $statuses = [
        \App\ExportOperation::STATUS_PENDING    => ['title' => 'ожидает', 'color' => 'muted', 'icon' => '&#128164;'],
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

<table class="table table-borderless">
    @foreach($export->operations->sortBy('ad_id')->groupBy('ad_id') as $adId => $operations)
        @foreach($operations as $op)
            <tr class="text-{{ $statuses[$op->status]['color'] }}"
                onclick='console.log("Загрузка {{ $op->id }}", @json($op))'>
                <td class="fit" title="{{ $statuses[$op->status]['title'] }}">
                    <small>{!! $statuses[$op->status]['icon'] !!}</small>
                </td>
                <td class="fit" title="Объявление ID {{ $adId }}">
                    <span>{{ $adId }}</span>
                </td>
                <td>
                    <span>{{ $types[$op->type] }}:</span>
                    @foreach($op->state_to as $field => $newValue)
                        <strong>{{ $field }}{{ $loop->last ? '': ', ' }}</strong>
                    @endforeach
                </td>
            </tr>
        @endforeach
    @endforeach
</table>
@endsection
