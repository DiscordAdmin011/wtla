/* Public-site interactions: image gallery thumbnail switching. */
(function () {
    'use strict';

    var main = document.getElementById('galleryMain');
    var thumbs = document.querySelectorAll('.gallery__thumb');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var full = thumb.getAttribute('data-full');
            if (main && full) {
                main.src = full;
            }
            thumbs.forEach(function (t) { t.classList.remove('is-active'); });
            thumb.classList.add('is-active');
        });
    });
})();
