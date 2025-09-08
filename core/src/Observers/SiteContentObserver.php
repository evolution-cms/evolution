<?php namespace EvolutionCMS\Observers;

use EvolutionCMS\Models\SiteContent;

class SiteContentObserver
{
    public function saving(SiteContent $model) : bool
    {
        $model->editedby = evo()->getLoginUserID('mgr');
        $model->pagetitle = trim($model->pagetitle);

        return !empty($model->pagetitle);
    }
}
