@extends('layout')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <h4><a name="how-to-edit"></a>Как редактировать объявления?</h4>
            <p>Редактирование проходит в три шага:</p>
            <ol>
                <li><strong>подготовка таблицы</strong> с актуальным состоянием объявлений. Для этого нужно выбрать клиента и кампании;</li>
                <li><strong>редактирование</strong> подготовленной таблицы с помощью интерфейса Google Spreadsheets;</li>
                <li><strong>загрузка</strong> изменений в ВК;</li>
            </ol>
            <p>Наблюдать за процессом загрузки, посмотреть результаты работы и обнаружить возможные проблемы можно на странице загрузки в соответствующем разделе.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-8">
            <h4><a name="editable-fields"></a>Список редактируемых полей в таблице</h4>
            <ul>
                @foreach($fields as $name => $field)
                    <li> <strong>{{ $name }}</strong> - {{ $field['desc'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-8">
            <h4><a name="caveats"></a>Известные ограничения редактирования</h4>
            <ul>
                <li>Текст кнопки поста может быть не ожидаемо заменен в процессе редактирования, если он был не из списка доступных для установки через API (см. описание поля link_button, столбец "Текст" в <a href="https://vk.com/dev/wall.postAdsStealth" target="_blank">таблице</a>) </li>
            </ul>
        </div>
    </div>
@endsection
