// this is called from base.html
function dataLayerSetup() {
    const printLink         = document.querySelector('.app-c-print-link__link');
    const accordionButtons  = document.querySelectorAll('.govuk-accordion__section-button');
    const ctaButtons        = document.querySelectorAll('.govuk-button');

    printLink != null           ? assignPrintLinkWithAction(printLink) : null;
    accordionButtons != null    ? assignAccordionWithAction(accordionButtons) : null;
    ctaButtons != null          ? assignCtaButtonWithAction(ctaButtons) : null;
}

function assignPrintLinkWithAction(printLink){
    printLink.addEventListener('click', () => {
        pushToDataLayer({
            "event":        'print_page',
            "link_text":    printLink.textContent.trim()
        });
    });
}

function assignCtaButtonWithAction(ctaButtons){
    ctaButtons.forEach(ctaButton => {
        ctaButton.addEventListener('click', () => {
            
            if (ctaButton.href != null){
                pushToDataLayer({
                    "event":        'cta_button_click',
                    "link_text":    ctaButton.innerText,
                    "link_url":     ctaButton.href
                });
            }
        });
    });
}

function assignAccordionWithAction(accordionButtons){
    accordionButtons.forEach(accordionButton => {
        accordionButton.addEventListener('click', () => {
            accordionButtonClick(accordionButton);
        });
    });
}

function accordionButtonClick(accordionButton){
    pushToDataLayer({
        "event":                'accordion_use',
        "link_text":            accordionButton.querySelector('span').textContent.trim(),
        "interaction_type":     accordionButton.ariaExpanded === "true" ? "collapsing" : "expanding"
    });
}

//this is only ran on show framework and show supplier page
function frameworkAndSupplierPage() {
    const externalLinks = Array.from(document.querySelectorAll('main a')).filter(link => !(link.href.includes('crowncommercial') || link.href.includes('tel')) );

    externalLinks.forEach(link => {
        link.addEventListener('click', () => {
            pushToDataLayer({
                "event":        'portal_click',
                "link_text":    link.textContent.trim(),
                "link_url":     link.href
            });
        });
    });
}

function searchFilterAgreement() {
    document.addEventListener('DOMContentLoaded', () => {
        const statusCheckboxes = document.querySelectorAll('input[type="checkbox"]');

        statusCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('click', () => {
                pushToDataLayer({
                    "event":                'search_filter',
                    "interaction_type":     checkbox.checked ? "select" : "remove" ,
                    "interaction_detail":   checkbox.value
                });
            });
        });
    })
}

function onPageFeedback(feedbackResponse) {
    let feedbackCheckboxes;
    let feedbackChecked = [];

    if (feedbackResponse === 'yes') {
        feedbackCheckboxes = document.querySelectorAll("[name='useful']");
        feedbackCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                feedbackChecked.push(checkbox.value);
            }
        })

        pushToDataLayer({
            'event': 'feedback',
            'feedback': feedbackChecked,
            'feedback_type': 'useful'
        });

    } else {
        feedbackCheckboxes = document.querySelectorAll("[name='not-useful']");
        feedbackCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                feedbackChecked.push(checkbox.value);
            }
        })

        pushToDataLayer({
            'event': 'feedback',
            'feedback': feedbackChecked,
            'feedback_type': 'not_useful'
        });
    }
}

function formStart(formType) {
    const firstField = document.querySelectorAll('input[type="text"], textarea')[0];

    firstField.addEventListener('input', () => {
        console.log("event is captured only once. ");
        pushToDataLayer({
            "event":        'form_start',
            'form_type':    formType,
            'form_id':      document.querySelector('[name="subject"]').value
        })
      }, { once: true });
}

function fileDownload( fileURL, fileName, fileSize) {

    const fileSizeString = formatFileSize(fileSize);
    const fileType = fileURL.substring(fileURL.lastIndexOf('.')+1);
    const actualFileName = fileURL.substr(fileURL.lastIndexOf('/')+1,fileURL.lastIndexOf('.')-1 - fileURL.lastIndexOf('/'));

    pushToDataLayer({
        "event":        'file_download',
        "link_text": fileName,
        "link_url": fileURL,
        "file_extension": fileType,
        "file_size": fileSizeString,
        "file_name": actualFileName
    });

}

function pushToDataLayer(array) {
    array = (typeof array === 'string') ? JSON.parse(array) : array;
    var env = document.getElementById('app-env').dataset.env;

    if (env != "dev") {
        window.dataLayer.push(array);
    }
}

function formatFileSize(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}