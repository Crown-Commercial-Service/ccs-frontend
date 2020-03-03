/**
 * Feature test for local storage
 * @returns {boolean}
 */
function lsTest(){
    var test = 'lsTest';
    try {
        localStorage.setItem(test, test);
        localStorage.removeItem(test);
        return true;
    } catch(e) {
        return false;
    }
}


var elementIdsToRemember = ['name', 'email', 'phone', 'company'];

function wipeAllValues() {
    elementIdsToRemember.forEach(function (id) {
        localStorage.removeItem(id);
    });
}

function prepareInputs(id) {
    var $element = document.getElementById(id);
    $element.addEventListener('change', function (event) {
        localStorage.setItem(id, this.value);
    });

    var date = new Date();

    if (localStorage.getItem('_saved') === null) {
        localStorage.setItem('_saved', date.getTime());
        return;
    }

    var OneWeekInMs = 604800000;
    if (localStorage.getItem('_saved') <= date.getTime() - OneWeekInMs) {
        wipeAllValues();
    }

    // Fix for IE9
    var valueToUse = localStorage.getItem(id);
    if(valueToUse == null) {
        valueToUse = '';
    }

    $element.value = valueToUse;
    localStorage.setItem('_saved', date.getTime());
}


/**
 * Only run if local storage is available
 */
if(lsTest() === true){
    elementIdsToRemember.forEach(prepareInputs);
}
