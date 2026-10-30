<?php
/**
 * Evolution CMS validation.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Georgian
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

    'accepted' => 'ველი :attribute უნდა იყოს მიღებული.',
    'active_url' => 'ველი :attribute არ არის სწორი URL.',
    'after' => 'ველის :attribute თარიღი უნდა იყოს :date თარიღის შემდეგ.',
    'after_or_equal' => 'ველის :attribute თარიღი უნდა იყოს :date თარიღის შემდეგ ან მისი ტოლი.',
    'alpha' => 'ველი :attribute შეიძლება შეიცავდეს მხოლოდ ასოებს.',
    'alpha_dash' => 'ველი :attribute შეიძლება შეიცავდეს მხოლოდ ასოებს, ციფრებს, დეფისებსა და ქვედა ტირეებს.',
    'alpha_num' => 'ველი :attribute შეიძლება შეიცავდეს მხოლოდ ასოებსა და ციფრებს.',
    'array' => 'ველი :attribute უნდა იყოს მასივი.',
    'before' => 'ველის :attribute თარიღი უნდა იყოს :date თარიღამდე.',
    'before_or_equal' => 'ველის :attribute თარიღი უნდა იყოს :date თარიღამდე ან მისი ტოლი.',
    'between' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს :min-დან :max-მდე.',
        'file' => 'ველის :attribute ფაილის ზომა უნდა იყოს :min-დან :max კილობაიტამდე.',
        'string' => 'ველი :attribute უნდა შეიცავდეს :min-დან :max სიმბოლომდე.',
        'array' => 'ველი :attribute უნდა შეიცავდეს :min-დან :max ელემენტამდე.',
    ],
    'boolean' => 'ველის :attribute მნიშვნელობა უნდა იყოს ჭეშმარიტი ან მცდარი.',
    'confirmed' => 'ველის :attribute დადასტურება არ ემთხვევა.',
    'date' => 'ველი :attribute არ არის სწორი თარიღი.',
    'date_equals' => 'ველის :attribute თარიღი უნდა იყოს :date თარიღის ტოლი.',
    'date_format' => 'ველი :attribute არ შეესაბამება ფორმატს :format.',
    'different' => 'ველები :attribute და :other უნდა განსხვავდებოდეს.',
    'digits' => 'ველი :attribute უნდა შეიცავდეს :digits ციფრს.',
    'digits_between' => 'ველი :attribute უნდა შეიცავდეს :min-დან :max ციფრამდე.',
    'dimensions' => 'ველში :attribute გამოსახულების ზომები არასწორია.',
    'distinct' => 'ველს :attribute აქვს დუბლირებული მნიშვნელობა.',
    'email' => 'ველი :attribute უნდა იყოს ელფოსტის სწორი მისამართი.',
    'ends_with' => 'ველი :attribute უნდა მთავრდებოდეს ერთ-ერთი შემდეგი მნიშვნელობით: :values.',
    'exists' => 'არჩეული :attribute არასწორია.',
    'file' => 'ველი :attribute უნდა იყოს ფაილი.',
    'filled' => 'ველი :attribute უნდა შეიცავდეს მნიშვნელობას.',
    'gt' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს :value-ზე მეტი.',
        'file' => 'ველის :attribute ფაილის ზომა უნდა აღემატებოდეს :value კილობაიტს.',
        'string' => 'ველი :attribute უნდა შეიცავდეს :value-ზე მეტ სიმბოლოს.',
        'array' => 'ველი :attribute უნდა შეიცავდეს :value-ზე მეტ ელემენტს.',
    ],
    'gte' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს :value-ზე მეტი ან მისი ტოლი.',
        'file' => 'ველის :attribute ფაილის ზომა უნდა იყოს სულ მცირე :value კილობაიტი.',
        'string' => 'ველი :attribute უნდა შეიცავდეს სულ მცირე :value სიმბოლოს.',
        'array' => 'ველი :attribute უნდა შეიცავდეს სულ მცირე :value ელემენტს.',
    ],
    'image' => 'ველი :attribute უნდა იყოს გამოსახულება.',
    'in' => 'არჩეული :attribute არასწორია.',
    'in_array' => 'ველი :attribute არ არსებობს ველში :other.',
    'integer' => 'ველი :attribute უნდა იყოს მთელი რიცხვი.',
    'ip' => 'ველი :attribute უნდა იყოს სწორი IP მისამართი.',
    'ipv4' => 'ველი :attribute უნდა იყოს სწორი IPv4 მისამართი.',
    'ipv6' => 'ველი :attribute უნდა იყოს სწორი IPv6 მისამართი.',
    'json' => 'ველი :attribute უნდა იყოს სწორი JSON სტრიქონი.',
    'lt' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს :value-ზე ნაკლები.',
        'file' => 'ველის :attribute ფაილის ზომა უნდა იყოს :value კილობაიტზე ნაკლები.',
        'string' => 'ველი :attribute უნდა შეიცავდეს :value-ზე ნაკლებ სიმბოლოს.',
        'array' => 'ველი :attribute უნდა შეიცავდეს :value-ზე ნაკლებ ელემენტს.',
    ],
    'lte' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს :value-ზე ნაკლები ან მისი ტოლი.',
        'file' => 'ველის :attribute ფაილის ზომა არ უნდა აღემატებოდეს :value კილობაიტს.',
        'string' => 'ველი :attribute არ უნდა შეიცავდეს :value-ზე მეტ სიმბოლოს.',
        'array' => 'ველი :attribute არ უნდა შეიცავდეს :value-ზე მეტ ელემენტს.',
    ],
    'max' => [
        'numeric' => 'ველის :attribute მნიშვნელობა არ უნდა აღემატებოდეს :max-ს.',
        'file' => 'ველის :attribute ფაილის ზომა არ უნდა აღემატებოდეს :max კილობაიტს.',
        'string' => 'ველი :attribute არ უნდა შეიცავდეს :max-ზე მეტ სიმბოლოს.',
        'array' => 'ველი :attribute არ უნდა შეიცავდეს :max-ზე მეტ ელემენტს.',
    ],
    'mimes' => 'ველის :attribute ფაილის ტიპი უნდა იყოს: :values.',
    'mimetypes' => 'ველის :attribute ფაილის ტიპი უნდა იყოს: :values.',
    'min' => [
        'numeric' => 'ველის :attribute მნიშვნელობა უნდა იყოს სულ მცირე :min.',
        'file' => 'ველის :attribute ფაილის ზომა უნდა იყოს სულ მცირე :min კილობაიტი.',
        'string' => 'ველი :attribute უნდა შეიცავდეს სულ მცირე :min სიმბოლოს.',
        'array' => 'ველი :attribute უნდა შეიცავდეს სულ მცირე :min ელემენტს.',
    ],
    'not_in' => 'არჩეული :attribute არასწორია.',
    'not_regex' => 'ველის :attribute ფორმატი არასწორია.',
    'numeric' => 'ველი :attribute უნდა იყოს რიცხვი.',
    'password' => 'პაროლი არასწორია.',
    'present' => 'ველი :attribute უნდა არსებობდეს.',
    'regex' => 'ველის :attribute ფორმატი არასწორია.',
    'required' => 'ველის :attribute შევსება აუცილებელია.',
    'required_if' => 'ველის :attribute შევსება აუცილებელია, როცა ველის :other მნიშვნელობაა :value.',
    'required_unless' => ':attribute ველი აუცილებელია, თუ :other არ არის :values სიაში.',
    'required_with' => ':attribute ველი აუცილებელია, როცა :values მითითებულია.',
    'required_with_all' => ':attribute ველი აუცილებელია, როცა ყველა :values მითითებულია.',
    'required_without' => ':attribute ველი აუცილებელია, როცა :values არ არის მითითებული.',
    'required_without_all' => ':attribute ველი აუცილებელია, როცა არცერთი :values არ არის მითითებული.',
    'same' => ':attribute და :other უნდა ემთხვეოდეს ერთმანეთს.',
    'size' => [
        'numeric' => ':attribute უნდა იყოს :size.',
        'file' => ':attribute უნდა იყოს :size კილობაიტი.',
        'string' => ':attribute უნდა შეიცავდეს :size სიმბოლოს.',
        'array' => ':attribute უნდა შეიცავდეს :size ელემენტს.',
    ],
    'starts_with' => ':attribute უნდა იწყებოდეს ერთ-ერთი შემდეგი მნიშვნელობით: :values.',
    'string' => ':attribute უნდა იყოს ტექსტური სტრიქონი.',
    'timezone' => ':attribute უნდა იყოს მოქმედი დროის სარტყელი.',
    'unique' => ':attribute უკვე გამოყენებულია.',
    'uploaded' => ':attribute ვერ აიტვირთა.',
    'url' => ':attribute ფორმატი არასწორია.',
    'uuid' => ':attribute უნდა იყოს მოქმედი UUID.',

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
