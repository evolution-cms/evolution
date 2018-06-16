<?php namespace EvolutionCMS\Support;

use EvolutionCMS\Interfaces\DataSetPagerInterface;

#
# DataSetPager Class
# Created By Raymond Irving 2-Dec,2004
# Based on CLASP 2.0 (www.claspdev.com)
# -----------------------------------------
# Licensed under the GPL
# -----------------------------------------
#

class DataSetPager implements DataSetPagerInterface{

	public $ds; // datasource
    public $pageSize;
    public $pageNumber;
    public $rows;
    public $pager;
    public $id;

	// normal page
    public $pageStyle;
    public $pageClass;

	// selected page
    public $selPageStyle;
    public $selPageClass;
    public $renderRowFnc;
    public $renderRowFncArgs;
    public $renderPagerFnc;
    public $renderPagerFncArgs;
    public static $dataSetPagerCnt;

    public function __construct($id, $ds, $pageSize = 10, $pageNumber = -1) {
		global $_PAGE; // use view state object

		// set id
		self::$dataSetPagerCnt++;
		$this->id = !empty($id) ? $id : "dsp" . self::$dataSetPagerCnt;

		// get pagenumber
		// by setting pager to -1 cause pager to load it's last page number
		if($pageNumber == -1) {
			$pageNumber = 1;
			if(isset($_GET["dpgn" . $this->id])) {
				$pageNumber = $_GET["dpgn" . $this->id];
			} elseif(isset($_PAGE['vs'][$id . '_dpgn'])) {
				$pageNumber = $_PAGE['vs'][$id . '_dpgn'];
			}
		}
		if(!is_numeric($pageNumber)) {
			$pageNumber = 1;
		}

		$this->ds = $ds; // datasource
		$this->pageSize = $pageSize;
		$this->pageNumber = $pageNumber;
		$this->rows = '';
		$this->pager = '';
	}

    public function getRenderedPager() {
		return $this->pager;
	}

    public function getRenderedRows() {
		return $this->rows;
	}

    public function setDataSource($ds) {
		$this->ds = $ds;
	}

    public function setPageSize($ps) {
		$this->pageSize = $ps;
	}

    public function setRenderRowFnc($fncName, $args = "") {
		$this->renderRowFnc = &$fncName;
		$this->renderRowFncArgs = $args;    // extra agruments


	}

    public function setRenderPagerFnc($fncName, $args = "") {
		$this->renderPagerFnc = $fncName;
		$this->renderPagerFncArgs = $args;    // extra agruments
	}

    public function render() {
		$modx = evolutionCMS(); global $_PAGE;

		$isDataset = $modx->getDatabase()->isResult($this->ds);

		if(!$this->selPageStyle) {
			$this->selPageStyle = "font-weight:bold";
		}

		// get total number of rows
		$tnr = ($isDataset) ? $modx->getDatabase()->getRecordCount($this->ds) : count($this->ds);

		// render: no records found
		if($tnr <= 0) {
			$fnc = $this->renderRowFnc;
			$args = $this->renderRowFncArgs;
			if(isset($fnc)) {
				if($args != "") {
					$this->rows .= $fnc(0, null, $args);
				} // if agrs was specified then we will pass three params
				else {
					$this->rows .= $fnc(0, null);
				}                 // otherwise two will be passed
			}
			return;
		}

		// get total pages
		$tp = ceil($tnr / $this->pageSize);
		if($this->pageNumber > $tp) {
			$this->pageNumber = 1;
		}

		// get page number
		$p = $this->pageNumber;

		// save page number to view state if available
		if(isset($_PAGE['vs'])) {
			$_PAGE['vs'][$this->id . '_dpgn'] = $p;
		}

		// render pager : renderPagerFnc($cuurentPage,$pagerNumber,$arguments="");
		if($tp > 1) {
		    $url = '';
			$fnc = $this->renderPagerFnc;
			$args = $this->renderPagerFncArgs;
			if(!isset($fnc)) {
				if($modx->isFrontend()) {
					$url = $modx->makeUrl($modx->documentIdentifier, '', '', 'full') . '?';
				} else {
					$url = $_SERVER['PHP_SELF'] . '?';
				}
				$i = 0;
				foreach($_GET as $n => $v) if($n != 'dpgn' . $this->id) {
					$i++;
					$url .= (($i > 1) ? "&" : "") . "$n=$v";
				}
				if($i >= 1) {
					$url .= "&";
				}
			}
			for($i = 1; $i <= $tp; $i++) {
				if(isset($fnc)) {
					if($args != "") {
						$this->pager .= $fnc($p, $i, $args);
					} else {
						$this->pager .= $fnc($p, $i);
					}
				} else {
					$this->pager .= ($p == $i) ? " <span class='" . $this->selPageClass . "' style='" . $this->selPageStyle . "'>$i</span> " : " <a href='" . $url . "dpgn" . $this->id . "=$i' class='" . $this->pageClass . "' style='" . $this->pageStyle . "'>$i</a> ";
				}
			}
		}

		// render row : renderRowFnc($rowNumber,$row,$arguments="")
		$fnc = $this->renderRowFnc;
		$args = $this->renderRowFncArgs;

		if(isset($fnc)) {
			$i = 1;
			$fncObject = is_object($fnc);
			$minitems = (($p - 1) * $this->pageSize) + 1;
			$maxitems = (($p - 1) * $this->pageSize) + $this->pageSize;
			while($i <= $maxitems && ($row = ($isDataset) ? $modx->getDatabase()->getRow($this->ds) : $this->ds[$i - 1])) {
				if($i >= $minitems && $i <= $maxitems) {
					if($fncObject) {
						if($args != "") {
							$this->rows .= $fnc->RenderRowFnc($i, $row, $args);
						} else {
							$this->rows .= $fnc->RenderRowFnc($i, $row);
						}
					} else {
						if($args != "") {
							$this->rows .= $fnc($i, $row, $args);
						} // if agrs was specified then we wil pass three params
						else {
							$this->rows .= $fnc($i, $row);
						}                 // otherwise two will be passed
					}

				}
				$i++;
			}
		}
	}
}
