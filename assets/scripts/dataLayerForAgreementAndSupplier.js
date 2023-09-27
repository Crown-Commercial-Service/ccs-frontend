//this is only ran on show framework and show supplier page
window.onload = function() {
    const externalLinks = document.querySelectorAll('main a');

    externalLinks.forEach(link => {
    if (!(link.href.includes('crowncommercial') || link.href.includes('tel'))) {
        link.addEventListener('click', () => {
            portal_click(link.textContent.trim(), link.href);
        });
    }
    });
}