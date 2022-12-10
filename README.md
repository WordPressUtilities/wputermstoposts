# WPU Terms to Posts
Link terms to posts from the term edit page.


How to add to your term edit page :
---

Put the code below in your theme's functions.php file. Add new fields to your convenance.

```php
add_filter('wputtp_taxonomies', 'set_wputtp_taxonomies');
function set_wputtp_taxonomies($taxonomies) {
    $taxonomies['category'] = array(
        'post_types' => array('post')
    );
    return $taxonomies;
}
```


Roadmap :
---

- [ ] Display post thumbnail.
