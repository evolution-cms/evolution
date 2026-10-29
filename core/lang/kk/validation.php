<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Kazakh
 * @package Evolution CMS
 * @subpackage core
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

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

    'accepted' => ':attribute қабылдануы керек.',
    'active_url' => ':attribute жарамды URL емес.',
    'after' => ':attribute күні :date күнінен кейін болуы керек.',
    'after_or_equal' => ':attribute күні :date күніне тең немесе одан кейін болуы керек.',
    'alpha' => ':attribute тек әріптерден тұруы мүмкін.',
    'alpha_dash' => ':attribute тек әріптерден, сандардан, дефистерден және астын сызу таңбаларынан тұруы мүмкін.',
    'alpha_num' => ':attribute тек әріптер мен сандардан тұруы мүмкін.',
    'array' => ':attribute массив болуы керек.',
    'before' => ':attribute күні :date күнінен бұрын болуы керек.',
    'before_or_equal' => ':attribute күні :date күніне тең немесе одан бұрын болуы керек.',
    'between' => [
        'numeric' => ':attribute мәні :min және :max аралығында болуы керек.',
        'file' => ':attribute көлемі :min және :max килобайт аралығында болуы керек.',
        'string' => ':attribute ұзындығы :min және :max таңба аралығында болуы керек.',
        'array' => ':attribute ішіндегі элементтер саны :min және :max аралығында болуы керек.',
    ],
    'boolean' => ':attribute өрісі true немесе false болуы керек.',
    'confirmed' => ':attribute растауы сәйкес келмейді.',
    'date' => ':attribute жарамды күн емес.',
    'date_equals' => ':attribute күні :date күніне тең болуы керек.',
    'date_format' => ':attribute :format пішіміне сәйкес келмейді.',
    'different' => ':attribute және :other әртүрлі болуы керек.',
    'digits' => ':attribute :digits цифрдан тұруы керек.',
    'digits_between' => ':attribute :min және :max аралығындағы цифрлардан тұруы керек.',
    'dimensions' => ':attribute кескінінің өлшемдері жарамсыз.',
    'distinct' => ':attribute өрісінде қайталанатын мән бар.',
    'email' => ':attribute жарамды электрондық пошта мекенжайы болуы керек.',
    'ends_with' => ':attribute мына мәндердің бірімен аяқталуы керек: :values.',
    'exists' => 'Таңдалған :attribute жарамсыз.',
    'file' => ':attribute файл болуы керек.',
    'filled' => ':attribute өрісінің мәні болуы керек.',
    'gt' => [
        'numeric' => ':attribute мәні :value мәнінен үлкен болуы керек.',
        'file' => ':attribute көлемі :value килобайттан үлкен болуы керек.',
        'string' => ':attribute ұзындығы :value таңбадан көп болуы керек.',
        'array' => ':attribute ішіндегі элементтер саны :value мәнінен көп болуы керек.',
    ],
    'gte' => [
        'numeric' => ':attribute мәні :value мәніне тең немесе одан үлкен болуы керек.',
        'file' => ':attribute көлемі :value килобайтқа тең немесе одан үлкен болуы керек.',
        'string' => ':attribute ұзындығы :value таңбаға тең немесе одан көп болуы керек.',
        'array' => ':attribute ішінде кемінде :value элемент болуы керек.',
    ],
    'image' => ':attribute кескін болуы керек.',
    'in' => 'Таңдалған :attribute жарамсыз.',
    'in_array' => ':attribute өрісі :other ішінде жоқ.',
    'integer' => ':attribute бүтін сан болуы керек.',
    'ip' => ':attribute жарамды IP мекенжайы болуы керек.',
    'ipv4' => ':attribute жарамды IPv4 мекенжайы болуы керек.',
    'ipv6' => ':attribute жарамды IPv6 мекенжайы болуы керек.',
    'json' => ':attribute жарамды JSON жолы болуы керек.',
    'lt' => [
        'numeric' => ':attribute мәні :value мәнінен кіші болуы керек.',
        'file' => ':attribute көлемі :value килобайттан аз болуы керек.',
        'string' => ':attribute ұзындығы :value таңбадан аз болуы керек.',
        'array' => ':attribute ішіндегі элементтер саны :value мәнінен аз болуы керек.',
    ],
    'lte' => [
        'numeric' => ':attribute мәні :value мәніне тең немесе одан кіші болуы керек.',
        'file' => ':attribute көлемі :value килобайтқа тең немесе одан аз болуы керек.',
        'string' => ':attribute ұзындығы :value таңбаға тең немесе одан аз болуы керек.',
        'array' => ':attribute ішіндегі элементтер саны :value мәнінен аспауы керек.',
    ],
    'max' => [
        'numeric' => ':attribute мәні :max мәнінен аспауы керек.',
        'file' => ':attribute көлемі :max килобайттан аспауы керек.',
        'string' => ':attribute ұзындығы :max таңбадан аспауы керек.',
        'array' => ':attribute ішіндегі элементтер саны :max мәнінен аспауы керек.',
    ],
    'mimes' => ':attribute мына түрдегі файл болуы керек: :values.',
    'mimetypes' => ':attribute мына түрдегі файл болуы керек: :values.',
    'min' => [
        'numeric' => ':attribute мәні кемінде :min болуы керек.',
        'file' => ':attribute көлемі кемінде :min килобайт болуы керек.',
        'string' => ':attribute ұзындығы кемінде :min таңба болуы керек.',
        'array' => ':attribute ішінде кемінде :min элемент болуы керек.',
    ],
    'not_in' => 'Таңдалған :attribute жарамсыз.',
    'not_regex' => ':attribute пішімі жарамсыз.',
    'numeric' => ':attribute сан болуы керек.',
    'password' => 'Құпиясөз қате.',
    'present' => ':attribute өрісі болуы керек.',
    'regex' => ':attribute пішімі жарамсыз.',
    'required' => ':attribute өрісін толтыру қажет.',
    'required_if' => ':other мәні :value болғанда :attribute өрісін толтыру қажет.',
    'required_unless' => ':other мәні :values ішінде болмаса, :attribute өрісін толтыру қажет.',
    'required_with' => ':values болғанда :attribute өрісін толтыру қажет.',
    'required_with_all' => ':values барлығы болғанда :attribute өрісін толтыру қажет.',
    'required_without' => ':values болмағанда :attribute өрісін толтыру қажет.',
    'required_without_all' => ':values ешқайсысы болмағанда :attribute өрісін толтыру қажет.',
    'same' => ':attribute және :other сәйкес келуі керек.',
    'size' => [
        'numeric' => ':attribute мәні :size болуы керек.',
        'file' => ':attribute көлемі :size килобайт болуы керек.',
        'string' => ':attribute ұзындығы :size таңба болуы керек.',
        'array' => ':attribute ішінде :size элемент болуы керек.',
    ],
    'starts_with' => ':attribute мына мәндердің бірімен басталуы керек: :values.',
    'string' => ':attribute жол болуы керек.',
    'timezone' => ':attribute жарамды уақыт белдеуі болуы керек.',
    'unique' => ':attribute бұрын қолданылған.',
    'uploaded' => ':attribute жүктелмеді.',
    'url' => ':attribute пішімі жарамсыз.',
    'uuid' => ':attribute жарамды UUID болуы керек.',

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

    'attributes' => [],

];
