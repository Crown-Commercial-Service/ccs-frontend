//this is only ran on show framework and show supplier page
window.onload = function() {
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