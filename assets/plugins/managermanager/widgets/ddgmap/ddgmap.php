<?php
/**
 * mm_ddGMap
 * @version 1.0.2 (2012-02-12)
 * 
 * Позволяет интегрировать карту Google Maps для получения координат.
 * 
 * @copyright 2012, DivanDesign
 * http://www.DivanDesign.ru
 */

function mm_ddGMap($tvs, $roles='', $templates='', $w='auto', $h='400') {
	
	global $modx, $content, $mm_fields, $modx_lang_attribute;
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
		$output .= "//  -------------- mm_ddGMap :: Begin ------------- \n";
		
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
var sectionConteiner = $j("<div class=\"sectionHeader\">"+sectionName+"</div><div class=\"sectionBody tmplvars\"><div class=\"ddGMap\" style=\"'.$style.'\"></div></div>");
//Добавляем контейнер
coordinatesField.parents(".tab-page:first").append(sectionConteiner);
//Если координаты не заданны, то задаём дефолт
if(ddLatLng == "") ddLatLng = "55.19396010947335,61.3670539855957";
ddLatLng = ddLatLng.split(",");

//Callback функция для GM
window.ddgminitialize = function(){
	var GM = google.maps;
	var myOptions = {
		zoom: 15,
		center: new GM.LatLng(ddLatLng[0],ddLatLng[1]),
		mapTypeId: GM.MapTypeId.ROADMAP,
		streetViewControl: false,
		scrollwheel: false
	};
	var map = new GM.Map(sectionConteiner.find(".ddGMap").get(0), myOptions);
	//Добавляем маркер на карту
	var GMMarker = new GM.Marker({
		position: new GM.LatLng(ddLatLng[0],ddLatLng[1]),
		map: map,
		draggable: true
	});
	//При перетаскивании маркера
	GM.event.addListener(GMMarker, "drag", function(event){
		var position = event.latLng;//Координаты
		coordinatesField.val(position.lat() + "," + position.lng());//Сохраняем значение в поле
	});
	//При клике на карте
	GM.event.addListener(map, "click", function(event){
		var position = event.latLng;//Новые координаты
		GMMarker.setPosition(position);//Меняем позицию маркера
		map.setCenter(position);//Центрируем карту на маркере
		coordinatesField.val(position.lat() + "," + position.lng());//Сохраняем значение в поле
	});
};
//Подключаем карту, вызываем callback функцию
$j(window).on("load.ddEvents", function(){
	$j("body").append("<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false&hl='.$modx_lang_attribute.'&callback=ddgminitialize\">");
});
';
		}
		$output .= "//  -------------- mm_ddGMap :: End ------------- \n";
		
		$e->output($output . "\n");	// Send the output to the browser	
	} // end if
}
?>