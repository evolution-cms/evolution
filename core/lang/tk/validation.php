<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Turkmen
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

    'accepted' => ':attribute kabul edilmelidir.',
    'active_url' => ':attribute dogry URL däl.',
    'after' => ':attribute :date senesinden soňky sene bolmalydyr.',
    'after_or_equal' => ':attribute :date senesine deň ýa-da ondan soňky sene bolmalydyr.',
    'alpha' => ':attribute diňe harplary öz içine alyp biler.',
    'alpha_dash' => ':attribute diňe harplary, sanlary, defisleri we aşaky çyzyklary öz içine alyp biler.',
    'alpha_num' => ':attribute diňe harplary we sanlary öz içine alyp biler.',
    'array' => ':attribute massiw bolmalydyr.',
    'before' => ':attribute :date senesinden öňki sene bolmalydyr.',
    'before_or_equal' => ':attribute :date senesine deň ýa-da ondan öňki sene bolmalydyr.',
    'between' => [
        'numeric' => ':attribute :min bilen :max aralygynda bolmalydyr.',
        'file' => ':attribute :min bilen :max kilobaýt aralygynda bolmalydyr.',
        'string' => ':attribute :min bilen :max nyşan aralygynda bolmalydyr.',
        'array' => ':attribute :min bilen :max aralygynda elementi öz içine almalydyr.',
    ],
    'boolean' => ':attribute meýdany true ýa-da false bolmalydyr.',
    'confirmed' => ':attribute tassyklamasy gabat gelmeýär.',
    'date' => ':attribute dogry sene däl.',
    'date_equals' => ':attribute :date senesine deň sene bolmalydyr.',
    'date_format' => ':attribute :format formatyna gabat gelmeýär.',
    'different' => ':attribute bilen :other dürli bolmalydyr.',
    'digits' => ':attribute :digits sifrden ybarat bolmalydyr.',
    'digits_between' => ':attribute :min bilen :max aralygynda sifrden ybarat bolmalydyr.',
    'dimensions' => ':attribute suratynyň ölçegleri nädogry.',
    'distinct' => ':attribute meýdanynda gaýtalanýan baha bar.',
    'email' => ':attribute dogry e-poçta salgysy bolmalydyr.',
    'ends_with' => ':attribute şu bahalaryň biri bilen tamamlanmalydyr: :values.',
    'exists' => 'Saýlanan :attribute nädogry.',
    'file' => ':attribute faýl bolmalydyr.',
    'filled' => ':attribute meýdanynyň bahasy bolmalydyr.',
    'gt' => [
        'numeric' => ':attribute :value bahasyndan uly bolmalydyr.',
        'file' => ':attribute :value kilobaýtdan uly bolmalydyr.',
        'string' => ':attribute :value nyşandan uzyn bolmalydyr.',
        'array' => ':attribute :value sanyndan köp elementi öz içine almalydyr.',
    ],
    'gte' => [
        'numeric' => ':attribute :value bahasyna deň ýa-da ondan uly bolmalydyr.',
        'file' => ':attribute :value kilobaýta deň ýa-da ondan uly bolmalydyr.',
        'string' => ':attribute :value nyşana deň ýa-da ondan uzyn bolmalydyr.',
        'array' => ':attribute azyndan :value elementi öz içine almalydyr.',
    ],
    'image' => ':attribute surat bolmalydyr.',
    'in' => 'Saýlanan :attribute nädogry.',
    'in_array' => ':attribute meýdany :other içinde ýok.',
    'integer' => ':attribute bitin san bolmalydyr.',
    'ip' => ':attribute dogry IP salgysy bolmalydyr.',
    'ipv4' => ':attribute dogry IPv4 salgysy bolmalydyr.',
    'ipv6' => ':attribute dogry IPv6 salgysy bolmalydyr.',
    'json' => ':attribute dogry JSON setiri bolmalydyr.',
    'lt' => [
        'numeric' => ':attribute :value bahasyndan kiçi bolmalydyr.',
        'file' => ':attribute :value kilobaýtdan kiçi bolmalydyr.',
        'string' => ':attribute :value nyşandan gysga bolmalydyr.',
        'array' => ':attribute :value sanyndan az elementi öz içine almalydyr.',
    ],
    'lte' => [
        'numeric' => ':attribute :value bahasyna deň ýa-da ondan kiçi bolmalydyr.',
        'file' => ':attribute :value kilobaýta deň ýa-da ondan kiçi bolmalydyr.',
        'string' => ':attribute :value nyşana deň ýa-da ondan gysga bolmalydyr.',
        'array' => ':attribute :value sanyndan köp elementi öz içine almaly däldir.',
    ],
    'max' => [
        'numeric' => ':attribute :max bahasyndan uly bolmaly däldir.',
        'file' => ':attribute :max kilobaýtdan uly bolmaly däldir.',
        'string' => ':attribute :max nyşandan uzyn bolmaly däldir.',
        'array' => ':attribute :max sanyndan köp elementi öz içine almaly däldir.',
    ],
    'mimes' => ':attribute şu görnüşli faýl bolmaly: :values.',
    'mimetypes' => ':attribute şu görnüşli faýl bolmaly: :values.',
    'min' => [
        'numeric' => ':attribute azyndan :min bolmaly.',
        'file' => ':attribute azyndan :min kilobaýt bolmaly.',
        'string' => ':attribute azyndan :min nyşandan ybarat bolmaly.',
        'array' => ':attribute azyndan :min elementden ybarat bolmaly.',
    ],
    'not_in' => 'Saýlanan :attribute nädogry.',
    'not_regex' => ':attribute formaty nädogry.',
    'numeric' => ':attribute san bolmaly.',
    'password' => 'Parol nädogry.',
    'present' => ':attribute meýdany bar bolmaly.',
    'regex' => ':attribute formaty nädogry.',
    'required' => ':attribute meýdany hökmanydyr.',
    'required_if' => ':other bahasy :value bolanda :attribute meýdany hökmanydyr.',
    'required_unless' => ':other bahasy :values sanawynda bolmasa, :attribute meýdany hökmanydyr.',
    'required_with' => ':values bar bolanda :attribute meýdany hökmanydyr.',
    'required_with_all' => ':values hemmesi bar bolanda :attribute meýdany hökmanydyr.',
    'required_without' => ':values ýok bolanda :attribute meýdany hökmanydyr.',
    'required_without_all' => ':values hiç biri ýok bolanda :attribute meýdany hökmanydyr.',
    'same' => ':attribute we :other gabat gelmeli.',
    'size' => [
        'numeric' => ':attribute :size bolmaly.',
        'file' => ':attribute :size kilobaýt bolmaly.',
        'string' => ':attribute :size nyşandan ybarat bolmaly.',
        'array' => ':attribute :size elementden ybarat bolmaly.',
    ],
    'starts_with' => ':attribute şu bahalaryň biri bilen başlanmaly: :values.',
    'string' => ':attribute tekst setiri bolmaly.',
    'timezone' => ':attribute dogry wagt zolagy bolmaly.',
    'unique' => ':attribute eýýäm ulanylýar.',
    'uploaded' => ':attribute ýüklenip bilinmedi.',
    'url' => ':attribute formaty nädogry.',
    'uuid' => ':attribute dogry UUID bolmaly.',

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
            'rule-name' => 'ýörite-habar',
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
