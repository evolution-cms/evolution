/**
 *
 * Select Categories
 */
let ajax_url = 'index.php?a=121';
let categorizeWorkbench = document.getElementById('categorize-workbench');
let elementsSelect = document.getElementById('elements-select');
if (elementsSelect && categorizeWorkbench) {
  elementsSelect.onchange = function(e) {
    categorizeWorkbench.innerHTML = '';
    categorizeWorkbench.classList.add('ajax_loading');
    document.getElementById('categorize-elements').classList.remove('hidden');
    document.getElementById('categorize-formfields').innerHTML = '';
    let xhr = new XMLHttpRequest();
    xhr.open('POST', ajax_url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
    xhr.onload = function() {
      if (this.readyState === 4) {
        categorizeWorkbench.classList.remove('ajax_loading');
        categorizeWorkbench.innerHTML = this.responseText;
        evo.tooltips('#categorize-elements [data-tooltip]');
        evo.draggable('.drag', {
          handle: {
            start: function() {
              this.style.opacity = '.5';
              this.style.zIndex = 10000;
            }, end: function() {
              this.style.opacity = 1;
              this.style.zIndex = 1000;
            },
          }, container: {
            className: 'drop', classOver: 'over', over: function() {
              this.classList.add('over');
            }, leave: function() {
              this.classList.remove('over');
            }, drop: function(drag) {
              this.classList.remove('over');
              drag.classList.add('ok');
            },
          },
        });
      }
    };
    xhr.send(request_key + '[ajax]=1&' + request_key + '[task]=categorize_load_elements&' + request_key + '[elements]=' + e.target.value);
  };
}

/**
 * Sort Categories
 */
evo.sortable('.table-sortable tbody > tr', {
  complete: function() {
    let els = document.querySelectorAll('.table-sortable tbody > tr');
    for (let i = 0; i < els.length; i++) {
      els[i].querySelector('input.sort').value = i + 1;
      els[i].querySelector('span.sort').innerHTML = i + 1;
    }
  },
});

/**
 * collect the categorization in formfields
 *
 * @TODO collect them within a object and send by jason-request.
 */
let categorizeSubmit = document.getElementById('categorize-submit');
if (categorizeSubmit) {
  categorizeSubmit.addEventListener('mouseenter', function() {
  this.disabled = 'disabled';
  this.value = 'wait...';
  let categorizeFormfields = document.getElementById('categorize-formfields');
  categorizeFormfields.innerHTML = '';

  [].slice.call(document.querySelectorAll('div.categorize_category')).forEach(function(a) {
    let category_id = a.id.split('-')[2];
    let category_name = a.querySelector('h2').innerText.replace(/^\s+|\s+$/g, '');
    let elements = a.querySelectorAll('div.drag');

    if (elements.length > 0) {
      elements.forEach(function(element) {
        let element_id = element.id.split('-')[2];
        let element_name = element.querySelector('h4').innerText.replace(/^\s+|\s+$/g, '');
        let id_input_element = 'input-element-' + element_id;
        let id_input_element_name = 'input-element-name-' + element_id;
        let id_input_category_name = 'input-category-name-' + element_id;

        let input = document.createElement('input');
        input.type = 'text';
        input.id = id_input_element_name;
        input.name = request_key + '[categorize][elements][' + element_id + '][element_name]';
        input.value = element_name;
        categorizeFormfields.appendChild(input);

        input = document.createElement('input');
        input.type = 'text';
        input.id = id_input_element;
        input.name = request_key + '[categorize][elements][' + element_id + '][category_id]';
        input.value = category_id;
        categorizeFormfields.appendChild(input);

        input = document.createElement('input');
        input.type = 'text';
        input.id = id_input_category_name;
        input.name = request_key + '[categorize][elements][' + element_id + '][category_name]';
        input.value = category_name;
        categorizeFormfields.appendChild(input);
      });
    }
  });

  this.disabled = '';
  this.value = 'Save categorization';

  });
}
