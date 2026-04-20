<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute qəbul edilməlidir.',
'active_url' => ':attribute etibarlı URL deyil.',
'after' => ':attribute :date-dən sonra bir tarix olmalıdır.',
'after_or_equal' => ':attribute :date-dən sonra və ya ona bərabər bir tarix olmalıdır.',
'alpha' => ':attribute yalnız hərflərdən ibarət ola bilər.',
'alpha_dash' => ':attribute yalnız hərflər, rəqəmlər, tirelər və alt xətlərdən ibarət ola bilər.',
'alpha_num' => ':attribute yalnız hərflər və rəqəmlərdən ibarət ola bilər.',
'array' => ':attribute bir massiv olmalıdır.',
'before' => ':attribute :date-dən əvvəl bir tarix olmalıdır.',
'before_or_equal' => ':attribute :date-dən əvvəl və ya ona bərabər bir tarix olmalıdır.',
'between' => [
    'numeric' => ':attribute :min ilə :max arasında olmalıdır.',
    'file' => ':attribute :min ilə :max kilobayt arasında olmalıdır.',
    'string' => ':attribute :min ilə :max simvollar arasında olmalıdır.',
    'array' => ':attribute :min ilə :max arasında maddələrə sahib olmalıdır.',
],
'boolean' => ':attribute sahəsi doğru və ya yanlış olmalıdır.',
'confirmed' => ':attribute təsdiqi uyğun gəlmir.',
'date' => ':attribute etibarlı bir tarix deyil.',
'date_equals' => ':attribute :date ilə bərabər bir tarix olmalıdır.',
'date_format' => ':attribute :format formatına uyğun gəlmir.',
'different' => ':attribute və :other fərqli olmalıdır.',
'digits' => ':attribute :digits rəqəm olmalıdır.',
'digits_between' => ':attribute :min ilə :max arasında rəqəmlərə sahib olmalıdır.',
'dimensions' => ':attribute etibarsız şəkil ölçülərinə malikdir.',
'distinct' => ':attribute sahəsində təkrarlanan dəyər var.',
'email' => ':attribute etibarlı bir e-poçt ünvanı olmalıdır.',
'ends_with' => ':attribute aşağıdakilerdən biri ilə bitməlidir: :values.',
'exists' => 'Seçilmiş :attribute etibarsızdır.',
'file' => ':attribute bir fayl olmalıdır.',
'filled' => ':attribute sahəsi bir dəyərə sahib olmalıdır.',
'gt' => [
    'numeric' => ':attribute :value-dən böyük olmalıdır.',
    'file' => ':attribute :value kilobaytdan böyük olmalıdır.',
    'string' => ':attribute :value simvoldan böyük olmalıdır.',
    'array' => ':attribute :value-dən çox maddəyə sahib olmalıdır.',
],
'gte' => [
    'numeric' => ':attribute :value-dən böyük və ya ona bərabər olmalıdır.',
    'file' => ':attribute :value kilobaytdan böyük və ya ona bərabər olmalıdır.',
    'string' => ':attribute :value simvoldan böyük və ya ona bərabər olmalıdır.',
    'array' => ':attribute ən azı :value maddəyə sahib olmalıdır.',
],
'image' => ':attribute bir şəkil olmalıdır.',
'in' => 'Seçilmiş :attribute etibarsızdır.',
'in_array' => ':attribute sahəsi :other-də mövcud deyil.',
'integer' => ':attribute tam ədəd olmalıdır.',
'ip' => ':attribute etibarlı bir IP ünvanı olmalıdır.',
'ipv4' => ':attribute etibarlı bir IPv4 ünvanı olmalıdır.',
'ipv6' => ':attribute etibarlı bir IPv6 ünvanı olmalıdır.',
'json' => ':attribute etibarlı bir JSON sətiri olmalıdır.',
'lt' => [
    'numeric' => ':attribute :value-dən kiçik olmalıdır.',
    'file' => ':attribute :value kilobaytdan kiçik olmalıdır.',
    'string' => ':attribute :value simvoldan kiçik olmalıdır.',
    'array' => ':attribute :value maddədən az olmalıdır.',
],
'lte' => [
        'numeric' => ':attribute :value-dən kiçik və ya ona bərabər olmalıdır.',
        'file' => ':attribute :value kilobaytdan kiçik və ya ona bərabər olmalıdır.',
        'string' => ':attribute :value simvoldan kiçik və ya ona bərabər olmalıdır.',
        'array' => ':attribute :value maddədən çox olmamalıdır.',
],
'max' => [
        'numeric' => ':attribute :max-dan böyük olmamalıdır.',
        'file' => ':attribute :max kilobaytdan böyük olmamalıdır.',
        'string' => ':attribute :max simvoldan böyük olmamalıdır.',
        'array' => ':attribute :max maddədən çox olmamalıdır.',
],
    'mimes' => ':attribute aşağıdakı növdə fayl olmalıdır: :values.',
    'mimetypes' => ':attribute aşağıdakı növdə fayl olmalıdır: :values.',
    'min' => [
        'numeric' => ':attribute ən azı :min olmalıdır.',
        'file' => ':attribute ən azı :min kilobayt olmalıdır.',
        'string' => ':attribute ən azı :min simvol olmalıdır.',
        'array' => ':attribute ən azı :min maddəyə sahib olmalıdır.',
    ],
    'not_in' => 'Seçilmiş :attribute etibarsızdır.',
    'not_regex' => ':attribute formatı etibarsızdır.',
    'numeric' => ':attribute bir ədəd olmalıdır.',
    'password' => 'Şifrə səhvdir.',
    'present' => ':attribute sahəsi mövcud olmalıdır.',
    'regex' => ':attribute formatı etibarsızdır.',
    'required' => ':attribute sahəsi mütləqdir.',
    'required_if' => ':other :value olduğu zaman :attribute sahəsi mütləqdir.',
    'required_unless' => ':other :values daxil olmadığı təqdirdə :attribute sahəsi mütləqdir.',
    'required_with' => ':values mövcud olduqda :attribute sahəsi mütləqdir.',
    'required_with_all' => ':values mövcud olduqda :attribute sahəsi mütləqdir.',
    'required_without' => ':values mövcud olmadığı zaman :attribute sahəsi mütləqdir.',
    'required_without_all' => ':values heç biri mövcud olmadığı zaman :attribute sahəsi mütləqdir.',
    'same' => ':attribute və :other uyğun olmalıdır.',
    'size' => [
        'numeric' => ':attribute :size olmalıdır.',
        'file' => ':attribute :size kilobayt olmalıdır.',
        'string' => ':attribute :size simvol olmalıdır.',
        'array' => ':attribute :size maddə saxlamalıdır.',
    ],
    'starts_with' => ':attribute aşağıdakilerdən biri ilə başlamalıdır: :values.',
    'string' => ':attribute bir sətir olmalıdır.',
    'timezone' => ':attribute etibarlı bir zona olmalıdır.',
    'unique' => ':attribute artıq götürülüb.',
    'uploaded' => ':attribute yüklənmədi.',
    'url' => ':attribute formatı etibarsızdır.',
    'uuid' => ':attribute etibarlı bir UUID olmalıdır.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    //'attributes' => [],

];
