jQuery(document).ready(function() {
    wputermstoposts_set_filter();
});

/* ----------------------------------------------------------
  Filter
---------------------------------------------------------- */

function wputermstoposts_set_filter() {
    var $tableLines = jQuery('#wputermstoposts_table tr[data-line]'),
        lines = [],
        linesLen = 0;
    if ($tableLines.length < 1) {
        return;
    }

    /* Build lines */
    $tableLines.each(function(i, el) {
        var $line = jQuery(el);
        lines.push([$line, $line.attr('data-line')]);
    });
    linesLen = lines.length;

    /* Search event */
    function search_event(e) {
        /* Prevent clicks on */
        if (e.keyCode == 13) {
            e.preventDefault();
        }
        var filterVal = jQuery(this).val().toLowerCase();

        if (!filterVal) {
            $tableLines.attr('data-hidden', 0);
        }

        /* lines - i */
        for (var i = 0; i < linesLen; i++) {
            if (lines[i][1].indexOf(filterVal) == -1) {
                lines[i][0].attr('data-hidden', 1);
            }
            else {
                lines[i][0].attr('data-hidden', 0);
            }
        }
    }

    jQuery('#wputermstoposts_s').on('keyup', search_event);
}
