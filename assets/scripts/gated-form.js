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

    $element.value = localStorage.getItem(id);
    localStorage.setItem('_saved', date.getTime())
}

elementIdsToRemember.forEach(prepareInputs);