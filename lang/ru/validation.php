<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines - Russian
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Вы должны принять :attribute.',
    'accepted_if' => 'Вы должны принять :attribute, когда :other равно :value.',
    'active_url' => 'Значение :attribute не является действительным URL.',
    'after' => 'Значение :attribute должно быть датой после :date.',
    'after_or_equal' => 'Значение :attribute должно быть датой после или равной :date.',
    'alpha' => 'Значение :attribute должно содержать только буквы.',
    'alpha_dash' => 'Значение :attribute должно содержать только буквы, цифры, дефисы и подчёркивания.',
    'alpha_num' => 'Значение :attribute должно содержать только буквы и цифры.',
    'array' => 'Значение :attribute должно быть массивом.',
    'before' => 'Значение :attribute должно быть датой до :date.',
    'before_or_equal' => 'Значение :attribute должно быть датой до или равной :date.',
    'between' => [
        'array' => 'Количество элементов :attribute должно быть от :min до :max.',
        'file' => 'Размер файла :attribute должен быть от :min до :max килобайт.',
        'numeric' => 'Значение :attribute должно быть от :min до :max.',
        'string' => 'Количество символов :attribute должно быть от :min до :max.',
    ],
    'boolean' => 'Поле :attribute должно быть true или false.',
    'confirmed' => 'Подтверждение :attribute не совпадает.',
    'current_password' => 'Неверный пароль.',
    'date' => 'Значение :attribute не является датой.',
    'date_equals' => 'Значение :attribute должно быть датой равной :date.',
    'date_format' => 'Значение :attribute не соответствует формату :format.',
    'declined' => 'Значение :attribute должно быть отклонено.',
    'declined_if' => 'Значение :attribute должно быть отклонено, когда :other равно :value.',
    'different' => 'Значения :attribute и :other должны различаться.',
    'digits' => 'Значение :attribute должно содержать :digits цифр.',
    'digits_between' => 'Значение :attribute должно содержать от :min до :max цифр.',
    'dimensions' => 'Изображение :attribute имеет недопустимые размеры.',
    'distinct' => 'Поле :attribute содержит повторяющееся значение.',
    'email' => 'Значение :attribute должно быть действительным адресом email.',
    'ends_with' => 'Значение :attribute должно заканчиваться одним из: :values.',
    'enum' => 'Выбранное значение :attribute недопустимо.',
    'exists' => 'Выбранное значение :attribute недопустимо.',
    'file' => 'Значение :attribute должно быть файлом.',
    'filled' => 'Поле :attribute обязательно для заполнения.',
    'gt' => [
        'array' => 'Количество элементов :attribute должно быть больше :value.',
        'file' => 'Размер файла :attribute должен быть больше :value килобайт.',
        'numeric' => 'Значение :attribute должно быть больше :value.',
        'string' => 'Количество символов :attribute должно быть больше :value.',
    ],
    'gte' => [
        'array' => 'Количество элементов :attribute должно быть :value или больше.',
        'file' => 'Размер файла :attribute должен быть :value килобайт или больше.',
        'numeric' => 'Значение :attribute должно быть :value или больше.',
        'string' => 'Количество символов :attribute должно быть :value или больше.',
    ],
    'image' => 'Файл :attribute должен быть изображением.',
    'in' => 'Выбранное значение :attribute недопустимо.',
    'in_array' => 'Значение :attribute не существует в :other.',
    'integer' => 'Значение :attribute должно быть целым числом.',
    'ip' => 'Значение :attribute должно быть действительным IP-адресом.',
    'ipv4' => 'Значение :attribute должно быть действительным IPv4-адресом.',
    'ipv6' => 'Значение :attribute должно быть действительным IPv6-адресом.',
    'json' => 'Значение :attribute должно быть JSON строкой.',
    'lt' => [
        'array' => 'Количество элементов :attribute должно быть меньше :value.',
        'file' => 'Размер файла :attribute должен быть меньше :value килобайт.',
        'numeric' => 'Значение :attribute должно быть меньше :value.',
        'string' => 'Количество символов :attribute должно быть меньше :value.',
    ],
    'lte' => [
        'array' => 'Количество элементов :attribute должно быть :value или меньше.',
        'file' => 'Размер файла :attribute должен быть :value килобайт или меньше.',
        'numeric' => 'Значение :attribute должно быть :value или меньше.',
        'string' => 'Количество символов :attribute должно быть :value или меньше.',
    ],
    'mac_address' => 'Значение :attribute должно быть действительным MAC-адресом.',
    'max' => [
        'array' => 'Количество элементов :attribute не может превышать :max.',
        'file' => 'Размер файла :attribute не может быть больше :max килобайт.',
        'numeric' => 'Значение :attribute не может быть больше :max.',
        'string' => 'Количество символов :attribute не может превышать :max.',
    ],
    'min' => [
        'array' => 'Количество элементов :attribute должно быть не менее :min.',
        'file' => 'Размер файла :attribute должен быть не менее :min килобайт.',
        'numeric' => 'Значение :attribute должно быть не менее :min.',
        'string' => 'Количество символов :attribute должно быть не менее :min.',
    ],
    'not_in' => 'Выбранное значение :attribute недопустимо.',
    'not_regex' => 'Формат :attribute недопустим.',
    'numeric' => 'Значение :attribute должно быть числом.',
    'password' => [
        'letters' => ':attribute должен содержать хотя бы одну букву.',
        'mixed' => ':attribute должен содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => ':attribute должен содержать хотя бы одну цифру.',
        'symbols' => ':attribute должен содержать хотя бы один символ.',
        'uncompromised' => ':attribute был скомпрометирован. Выберите другой :attribute.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'regex' => 'Формат :attribute недопустим.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать ключи: :values.',
    'required_if' => 'Поле :attribute обязательно, когда :other равно :value.',
    'required_unless' => 'Поле :attribute обязательно, если :other не входит в :values.',
    'required_with' => 'Поле :attribute обязательно, когда присутствует :values.',
    'required_with_all' => 'Поле :attribute обязательно, когда присутствуют :values.',
    'required_without' => 'Поле :attribute обязательно, когда отсутствует :values.',
    'required_without_all' => 'Поле :attribute обязательно, когда отсутствуют все :values.',
    'same' => 'Значения :attribute и :other должны совпадать.',
    'size' => [
        'array' => 'Количество элементов :attribute должно быть равно :size.',
        'file' => 'Размер файла :attribute должен быть равен :size килобайт.',
        'numeric' => 'Значение :attribute должно быть равно :size.',
        'string' => 'Количество символов :attribute должно быть равно :size.',
    ],
    'starts_with' => 'Значение :attribute должно начинаться с одного из: :values.',
    'string' => 'Значение :attribute должно быть строкой.',
    'timezone' => 'Значение :attribute должно быть действительным часовым поясом.',
    'unique' => 'Такое значение :attribute уже существует.',
    'uploaded' => 'Загрузка :attribute не удалась.',
    'url' => 'Значение :attribute должно быть действительным URL.',
    'uuid' => 'Значение :attribute должно быть действительным UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Attribute Names
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'имя',
        'email' => 'электронная почта',
        'password' => 'пароль',
        'phone' => 'телефон',
        'content' => 'содержимое',
        'parent_comment' => 'родительский комментарий',
    ],

];



