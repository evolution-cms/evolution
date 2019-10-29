<?php namespace FormLister;

use jsonHelper;
/**
 * Trait SubmitProtection
 * @package FormLister
 */
trait SubmitProtection
{
    abstract public function isSubmitted();
    abstract public function getFormId();

    /**
     * Проверка повторной отправки формы
     * @return bool если защита сработала, то true
     */
    public function checkSubmitProtection()
    {
        $result = false;
        if ($this->isSubmitted() && $this->getCFGDef('protectSubmit', 1)) {
            $formId = $this->getFormId();
            $hash = $this->getFormHash();
            if (isset($_SESSION[$formId . '_hash'])
                && $_SESSION[$formId . '_hash'] == $hash
                && $hash != '') {
                $result = true;
                $this->addMessage($this->translate('form.protectSubmit'));
                $this->log('Submit protection enabled');
            }
        }

        return $result;
    }

    /**
     * Проверка повторной отправки в течение определенного времени, в секундах
     * @return bool если защита сработала, то true
     */
    public function checkSubmitLimit()
    {
        $submitLimit = $this->getCFGDef('submitLimit', 60);
        $result = false;
        $formId = $this->getFormId();
        if (isset($_SESSION[$formId . '_limit']) && $this->isSubmitted() && $submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$formId . '_limit']) {
                $result = true;
                $this->addMessage($this->translate('form.submitLimit') .
                    ($submitLimit >= 60
                        ? round($submitLimit / 60, 0) . ' ' . $this->translate('form.minutes') . '.'
                        : $submitLimit . ' ' . $this->translate('form.minutes') . '.'
                    ));
                $this->log('Submit limit enabled');
            } else {
                unset($_SESSION[$formId . '_limit']);
            } //time expired
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function setSubmitProtection()
    {
        $formId = $this->getFormId();
        if ($this->getCFGDef('protectSubmit', 1)) {
            $_SESSION[$formId . '_hash'] = $this->getFormHash();
        } //hash is set earlier
        if ($this->getCFGDef('submitLimit', 60) > 0) {
            $_SESSION[$formId . '_limit'] = time();
        }

        return $this;
    }

    /**
     * @return array|string
     */
    public function getFormHash()
    {
        $hash = array();
        $protectSubmit = $this->getCFGDef('protectSubmit', 1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = $this->config->loadArray($protectSubmit);
            foreach ($protectSubmit as $field) {
                $hash[] = $this->getField(trim($field));
            }
        } else //all required fields
        {
            foreach ($this->rules as $field => $rules) {
                foreach ($rules as $rule => $description) {
                    if ($rule == 'required') {
                        $hash[] = $this->getField($field);
                    }
                }
            }
        }
        if ($hash) {
            $hash = md5(jsonHelper::toJSON($hash));
        }

        return $hash;
    }
}
