$(function() {
    var enableStorage = (('sessionStorage' in window) && (window.sessionStorage !== null));
    $('input.refresh').click(function() {
        for (var i in window.sessionStorage) {
            if (enableStorage) {
                sessionStorage.clear();
            }
            location.reload();
        }
    });
});
