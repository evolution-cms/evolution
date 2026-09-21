<?php

class DATEPICKER {
	function __construct() {
	}

	function getDP() {
		$modx = evolutionCMS(); global $_lang;

		$tpl = file_get_contents(__DIR__ . '/datepicker.tpl');
		return EvolutionCMS()->parseText($tpl, $_lang, '[%', '%]');
	}
}
