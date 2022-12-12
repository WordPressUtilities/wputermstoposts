jQuery(document).ready(function() {
    wputermstoposts_set_filter();
    wputermstoposts_set_reorder();
});

/* ----------------------------------------------------------
  Filter
---------------------------------------------------------- */

function wputermstoposts_set_filter() {
    jQuery('.wputermstoposts_filter').each(function() {
        var $el = jQuery(this),
            $wrapper = $el.closest('.wputermstoposts-wrapper'),
            $count = $wrapper.find('.wputermstoposts-filters__count .count'),
            $list = $wrapper.find('.wputermstoposts_list').children();
        jQuery(this).on('input', function() {
            var _total = 0,
                _search = $el.val().toLowerCase();
            if (!_search) {
                $count.text($count.attr('data-total'));
                $list.attr('data-hidden', '0');
                return;
            }

            $list.each(function(i, el) {
                var _isVisible = (el.innerText.toLowerCase().indexOf(_search) !== -1);
                if (_isVisible) {
                    _total++;
                }
                el.setAttribute('data-hidden', _isVisible ? '0' : '1');
            });
            $count.text(_total);
        });
    });
}

/* ----------------------------------------------------------
  Reorder
---------------------------------------------------------- */

function wputermstoposts_set_reorder() {
    jQuery('.wputermstoposts_order').each(function() {
        jQuery(this).on('change', function() {
            var $el = jQuery(this),
                attrName = $el.find(":selected").attr('name'),
                attrOrder = $el.find(":selected").attr('data-order'),
                $list = $el.closest('.wputermstoposts-wrapper').find('.wputermstoposts_list').get(0);
            wputermstoposts_reorderList($list, attrName, attrOrder);
        });
    });
}

function wputermstoposts_reorderList(list, attrName, attrOrder) {
    // Get the list items and convert to an array
    var listItems = [].slice.call(list.children);

    // Sort the array in ascending order based on the values of the specified attribute
    listItems.sort(function(a, b) {
        if (attrOrder == 'desc') {
            var c = b;
            b = a;
            a = c;
        }
        var valA = a.getAttribute(attrName);
        var valB = b.getAttribute(attrName);
        if (attrName == 'data-post-title') {
            return valA < valB ? -1 : 1;
        }
        else {
            return valA - valB;
        }

    });

    // Append the sorted items back to the list
    listItems.forEach(function(item) {
        list.appendChild(item);
    });
}
