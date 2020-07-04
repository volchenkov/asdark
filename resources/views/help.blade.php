@extends('layout')

@section('content')
    <div class="row">
        <div class="col-8">
            <h4><a name="editable-fields"></a>Список редактируемых полей</h4>
            <ul>
                @foreach($fields as $name => $field)
                    <li> <strong>{{ $name }}</strong> - {{ $field['desc'] }}</li>
                @endforeach
            </ul>

            <h4 class="mt-5"><a name="caveats"></a>Известные ограничения редактирования</h4>
            <ul>
                <li>Редактирование элементов карусели поста пока не поддерживается</li>
                <li>На данный момент в одной загрузке допускается редактирование до 2000 объявлений (до 100, если с полями постов)</li>
                <li>Текст кнопки поста может быть не ожидаемо заменен в процессе редактирования, если он был не из списка доступных для установки через API (см. описание поля link_button, столбец "Текст" в <a href="https://vk.com/dev/wall.postAdsStealth" target="_blank">таблице</a>) </li>
            </ul>
        </div>
    </div>
@endsection
