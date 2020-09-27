@extends('layout')

@section('content')
    <div class="row">
        <div class="col-8">
            <details {{ request()->get('q') == 'how-to-edit' ? 'open' : ''}}>
                <summary class="mb-3"><span class="lead">Как редактировать объявления?</span></summary>
                <p>Редактирование проходит в три шага:</p>
                <ol>
                    <li><strong>подготовка таблицы</strong> с актуальным состоянием объявлений. Для этого нужно выбрать клиента и кампании;</li>
                    <li><strong>редактирование</strong> подготовленной таблицы с помощью интерфейса Google Spreadsheets;</li>
                    <li><strong>загрузка</strong> изменений в ВК;</li>
                </ol>
                <p>Наблюдать за процессом загрузки, посмотреть результаты работы и обнаружить возможные проблемы можно на странице загрузки в соответствующем разделе.</p>
            </details>
            <hr/>
        </div>
    </div>
    <div class="row">
        <div class="col-8">
            <details {{ request()->get('q') == 'editable-fields' ? 'open' : ''}}>
                <summary class="mb-3"><span class="lead">Список редактируемых полей в таблице</span></summary>
                <p>Слева указано имя столбца в Google таблице, справа - описание свойства объявления. В ячейках таблицы оказываются значения соответствующих свойств конкретных объявлений.</p>
                <ul>
                    @foreach($fields as $name => $field)
                        <li> <strong>{{ $name }}</strong> - {{ $field['desc'] }}</li>
                    @endforeach
                </ul>
                <p>Для удобства идентификации объявлений также добавлены несколько readonly столбцов.</p>
            </details>
            <hr/>
        </div>
    </div>
    <div class="row">
        <div class="col-8">
            <details {{ request()->get('q') == 'caveats' ? 'open' : ''}}>
                <summary class="mb-3"><span class="lead">Известные ограничения и проблемы редактирования</span></summary>
                <p>Существует ряд ограничений, которые стоит иметь ввиду при использовании сервиса:</p>
                <ul>
                    <li>Текст кнопки поста может быть не ожидаемо заменен в процессе редактирования, если он был не из списка доступных для установки через API (см. описание поля link_button, столбец "Текст" в <a href="https://vk.com/dev/wall.postAdsStealth" target="_blank">таблице</a>) </li>
                </ul>
            </details>
        </div>
    </div>
@endsection
