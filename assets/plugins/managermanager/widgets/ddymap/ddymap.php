<?php
/**
 * mm_ddYMap
 * @version 1.0.1 (2012-01-13)
 * 
 * Позволяет интегрировать карту Yandex Maps для получения координат.
 * 
 * @copyright 2012, DivanDesign
 * http://www.DivanDesign.ru
 */

function mm_ddYMap($tvs, $roles='', $templates='', $key='', $w='auto', $h='400') {
	if($key == '') return;

	global $modx, $content, $mm_fields;
	$e = &$modx->Event;
	
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)){
		
		// Your output should be stored in a string, which is outputted at the end
		// It will be inserted as a Javascript block (with jQuery), which is executed on document ready
		$output = '';		
		
		// if we've been supplied with a string, convert it into an array 
		$tvs = makeArray($tvs);
		
		// You might want to check whether the current page's template uses the TVs that have been
		// supplied, to save processing page which don't contain them
		
		// Which template is this page using?
		if (isset($content['template'])) {
			$page_template = $content['template'];
		} else {
			// If no content is set, it's likely we're adding a new page at top level. 
			// So use the site default template. This may need some work as it might interfere with a default template set by MM?
			$page_template = $modx->config['default_template']; 
		}
		
		$tvs = tplUseTvs($content['template'], $tvs);
		if ($tvs == false) {
			return;
		}		
		
		
		$style = 'width: '.$w.'px; height: '.$h.'px; position: relative; border: 1px solid #c3c3c3;';
		// We always put a JS comment, which makes debugging much easier
		$output .= "//  -------------- mm_ddYMap :: Begin ------------- \n";
		
		// We have functions to include JS or CSS external files you might need
		// The standard ModX API methods don't work here
		//$output .= includeJs('http://maps.google.com/maps/api/js?sensor=false');
		
		// Do something for each of the fields supplied
		foreach ($tvs as $tv) {
			// If it's a TV, we may need to map the field name, to what it's ID is.
			// This can be obtained from the mm_fields array
			$tv_id = 'tv'.$tv['id'];
			$output .= '
var coordinatesField = $j("#'.$tv_id.'");//TV с координатами
var ddLatLng = coordinatesField.val();//Координаты
//Скрываем поле, запоминаем название поля
var sectionName = coordinatesField.parents("tr:first").hide().find(".warning").text();
coordinatesField.parents("tr:first").prev("tr").hide();
//Контейнер для карты
var sectionConteiner = $j("<div class=\"sectionHeader\">"+sectionName+"</div><div class=\"sectionBody tmplvars\"><div class=\"ddYMap\" style=\"'.$style.'\"></div></div>");
//Добавляем контейнер
coordinatesField.parents(".tab-page:first").append(sectionConteiner);
//Если координаты не заданны, то задаём дефолт
if(ddLatLng == "") ddLatLng = "61.3670539855957,55.19396010947335";
ddLatLng = ddLatLng.split(",");

//Callback функция для YM
function ddyminitialize(){
	//Создаём карту
	var map = new YMaps.Map(sectionConteiner.find(".ddYMap").get(0));
	map.setCenter(new YMaps.GeoPoint(ddLatLng[0], ddLatLng[1]), 15);//Центрируем
	//Добавляем контролы
	map.addControl(new YMaps.TypeControl());
	map.addControl(new YMaps.ToolBar());
	map.addControl(new YMaps.Zoom());
	map.addControl(new YMaps.ScaleLine());
	//Создаём маркер
	var overlay = new YMaps.Placemark(new YMaps.GeoPoint(ddLatLng[0], ddLatLng[1]),{draggable:true,hasBalloon: false});
	//Клик по карте
	YMaps.Events.observe(map, map.Events.Click, function (map, mEvent) {
		overlay.setGeoPoint(mEvent.getGeoPoint());
		coordinatesField.val(mEvent.getGeoPoint().toString());
	});
	//Перетаскивание маркера на карте
	YMaps.Events.observe(overlay, overlay.Events.Drag, function (mEvent) {
		coordinatesField.val(mEvent.getGeoPoint().toString());
	});
	map.addOverlay(overlay);//Добавляем на карту
}
//Подключаем карту
$j("head").append("<script type=\"text/javascript\" src=\"http://api-maps.yandex.ru/1.1/index.xml?loadByRequire=1&key='.$key.'\">");
$j(window).on("load.ddEvents",function(){
	YMaps.load(ddyminitialize);
});
';
		}
		$output .= "//  -------------- mm_ddYMap :: End ------------- \n";

		$e->output($output . "\n");	// Send the output to the browser
	} // end if
}
?>