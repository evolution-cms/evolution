<?php
/**
 * this is the old error handler. Here for legacy, until i replace all the old errors.
 *
 * @deprecated EvolutionCMS\Legacy\ErrorHandler
 * @todo could be unnecessary
 */
class errorHandler extends EvolutionCMS\Legacy\ErrorHandler {
    public function include_lang($context = 'common')
    {
        parent::includeLang($context);
    }
}
