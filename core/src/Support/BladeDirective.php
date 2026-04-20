<?php namespace EvolutionCMS\Support;

class BladeDirective
{
    //----------
    /**
     * @deprecated
     * @since 3.5.3
     *
     * It's not using anywhere.
     *
     * @todo [remove@3.7] Remove in Evolution CMS 3.7
     */
    public static function csrf(): string
    {
        return '<?php echo csrf_field();?>';
    }
    public static function evoLang($key): string
    {
        return '<?php echo ManagerTheme::getLexicon(' . $key . ');?>';
    }
    public static function evoStyle($key): string
    {
        return '<?php echo ManagerTheme::getStyle(' . $key . ');?>';
    }
    public static function evoAdminLang(): string
    {
        return '<?php echo ManagerTheme::getLangName();?>';
    }
    public static function evoCharset(): string
    {
        return '<?php echo ManagerTheme::getCharset();?>';
    }
    public static function evoAdminThemeUrl(): string
    {
        return '<?php echo ManagerTheme::getThemeUrl();?>';
    }
    public static function evoAdminThemeName(): string
    {
        return '<?php echo ManagerTheme::getTheme();?>';
    }
    //----------
}
