
// make freetext headings equal height to align paragraph content
function makeHeadingEqualHeight () {
     var leftHeading = document.querySelector('.intro-freetext-heading-left');
     var rightHeading = document.querySelector('.intro-freetext-heading-right');
     var leftHeadingHeight = document.querySelector('.intro-freetext-heading-left').getBoundingClientRect().height;
     var rightHeadingHeight = document.querySelector('.intro-freetext-heading-right').getBoundingClientRect().height;

    if (leftHeadingHeight > rightHeadingHeight) {
        rightHeading.style.height = leftHeadingHeight + 'px';
    }

    else if (rightHeadingHeight > leftHeadingHeight) {
        leftHeading.style.height = rightHeadingHeight + 'px';
    } 
}

if (document.querySelector('.component.free-text')) {
    makeHeadingEqualHeight();
}