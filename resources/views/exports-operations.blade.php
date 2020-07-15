@extends('layout')

@section('content')

@php
$statuses = [
    \App\ExportOperation::STATUS_DONE       => ['title' => 'готова', 'color' => 'success'],
    \App\ExportOperation::STATUS_PENDING    => ['title' => 'в ожидании', 'color' => 'info'],
    \App\ExportOperation::STATUS_FAILED     => ['title' => 'ошибка', 'color' => 'danger'],
    \App\ExportOperation::STATUS_ABORTED    => ['title' => 'прекращена', 'color' => 'warning'],
    \App\ExportOperation::STATUS_PROCESSING => ['title' => 'в работе', 'color' => 'info'],
];
$types = [
    \App\ExportOperation::TYPE_UPDATE_AD   => 'объявление',
    \App\ExportOperation::TYPE_UPDATE_POST => 'пост объявления',
];
@endphp

<div class="row">
    <div class="col-md-12">
        <h3>Операции загрузки #{{ $export->id }} </h3>
        <table class="table table-sm mt-4">
            <thead>
                <tr>
                    <th scope="col" style="width: 16.66%" class="text-center">Статус</th>
                    <th scope="col">Изменения</th>
                </tr>
            </thead>
            @foreach($export->operations->sortBy('ad_id')->groupBy('ad_id') as $adId => $operations)
                <tbody>
                @foreach($operations as $operation)
                    <tr>
                        <td class="text-center">
                            <span class="text-{{ $statuses[$operation->status]['color'] }}">{{ $statuses[$operation->status]['title'] }}</span>
                        </td>
                        <td>
                            <span>{{ $types[$operation->type] }} #{{$adId}}</span>
                            @foreach($operation->state_to as $field => $newValue)
                            <div class="small">
                                <strong>{{ $field }}</strong>
                                <div class="text-muted px-3">{{ $operation->state_from[$field] }}</div>
                                <div class="px-3">{{ $newValue }}</div>
                            </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
</div>
@endsection
