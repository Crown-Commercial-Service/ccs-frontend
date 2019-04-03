/* global $ */

document.addEventListener("DOMContentLoaded", function (event) {


    if (typeof jQuery != 'undefined') {
        $('body').addClass('js-enabled');
        // Initialise all the GOV.UK Frontend components
        window.GOVUKFrontend.initAll();
    }


        // Check for if an element exists
        function exists (elem) {
            return (elem != null && (elem.length >= 0 || elem.innerHTML.length >= 0))
        }

        if (matchMedia) {
            // This matches the `desk` breakpoint in CSS
            var mq = window.matchMedia('(min-width: 769px)')
            // console.log(mq.matches);
            mq.addListener(updateARIAroles)
            // WidthChange(mq);
        }
        var contentToggler = document.querySelectorAll('[data-toggler]')

        function updateARIAroles () {

            if (exists(contentToggler)) {

                // This allows us to iterate on multiple elements [1]
                var index = 0
                for (index = 0; index < contentToggler.length; index++) {

                    // Find the element's toggle target by looking for data-toggle
                    // Note: we're using `getAttribute` instead of `dataset.` due to better browser support for the former
                    var target = contentToggler[index].getAttribute('data-toggler')
                    var contentTarget = []
                    contentTarget[index] = document.querySelector('[data-toggle="' + target + '"]')

                    if (mq.matches && target == 'nav') {

                        // Set default aria attributes
                        // Hide all elements
                        contentToggler[index].setAttribute('aria-expanded', true)
                        contentTarget[index].setAttribute('aria-hidden', false)
                        // contentTarget[index].setAttribute('hidden', true);

                    }
                    else {

                        // Set default aria attributes
                        // Hide all elements
                        contentToggler[index].setAttribute('aria-expanded', false)
                        contentTarget[index].setAttribute('aria-hidden', true)

                    }

                }

            } // end of exists

        } // end of function



    // call this on page load
    updateARIAroles();


        // Class toggler
        // This is used for the search and the main navigation
        // -----------------------------------------------------------------------------------------

        if (exists(contentToggler)) {

            // This allows us to iterate on multiple elements [1]
            var index = 0
            for (index = 0; index < contentToggler.length; index++) {

                // Find the toggle target by looking for data-toggle
                // Note: we're using `getAttribute` instead of `dataset.` due to better browser support for the former
                var target = contentToggler[index].getAttribute('data-toggler')
                var contentTarget = []
                contentTarget[index] = document.querySelector('[data-toggle="' + target + '"]')

                // Listen for click event, toggle attributes and class names
                contentToggler[index].addEventListener('click', function (e) {

                        // Check the target of the element event
                        // Note: we're using `getAttribute` instead of `dataset.` due to better browser support for the former
                        var target = this.getAttribute('data-toggler')
                        // class to toggle on the body

                        var contentTarget = document.querySelector('[data-toggle="' + target + '"]')

                        var state = this.getAttribute('aria-expanded') === 'false' ? true : false

                        // Loop through all elements because some elements might have the same target and their aria needs to be updated as well
                        var index = 0
                        for (index = 0; index < contentToggler.length; index++) {

                            // Note: we're using `getAttribute` instead of `dataset.` due to better browser support for the former
                            if (contentToggler[index].getAttribute('data-toggler') == target) {
                                contentToggler[index].setAttribute('aria-expanded', state)
                            }
                        }

                        contentTarget.setAttribute('aria-hidden', !state)

                        var toggledLabel = this.getAttribute('data-toggler-toggled')
                        var untoggledLabel = this.getAttribute('data-toggler-untoggled')

                        if (state && toggledLabel && untoggledLabel) {
                            // document.body.classList.add(bodyClass)
                            // Make sure there is a toggled label set, otherwise we'll accidentally replace the entire node of the element (which we don't want)
                            if (exists(toggledLabel)) {
                                this.innerHTML = toggledLabel
                            }
                        } else if (untoggledLabel) {
                            // Make sure there is a toggled label set, otherwise we'll accidentally replace the entire node of the element (which we don't want)
                            if (exists(untoggledLabel)) {
                                this.innerHTML = untoggledLabel
                            }
                            // document.body.classList.remove(bodyClass)
                        }

                        e.preventDefault()

                    }
                    ,
                    false
                )

                // }

            }

        } // end of exists



});
