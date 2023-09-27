window.onload = function() {
    const printLink = document.querySelector('.app-c-print-link__link');

    if (printLink != null) {
      printLink.addEventListener('click', () => {
        print_page(printLink.textContent.trim());
      });
    }
}

function search_interaction(interaction_type, interaction_detail){
    window.dataLayer.push({
        "event": 'search_interaction',
        "interaction_type":     interaction_type !== null ? interaction_type : null,
        "interaction_detail":   interaction_detail !== null ? interaction_detail : null
    });
}

function print_page(link_text){
    window.dataLayer.push({
        "event": 'print_page',
        "link_text":        link_text !== null ? link_text : null,
        "page_URL":         window.location.href
    });
}