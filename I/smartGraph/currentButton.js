$(function() {
    $(':submit.btn').each(function() {
        if (typeof $(this).attr('onclick') !== 'undefined') {
            var url = '?' + $(this).attr('onclick').replace(/.*\?/, '').replace(/'; return false;/, '');
            if (url == location.search) {
                $(this).addClass('currentBtn');
            }
        }
    })
});
