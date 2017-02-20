# dvm-facets

This is a Wordpress plugin that adds facets to Wordpress build in search. The plugin support three kinds of facets: Shared meta data, shared property and shared ancestor. 

## Faceted search explained

You probably know search facets from Google. Google’s result page allow you to show only certain types (or facets) of results – like images or videos.

You choose which facet to show by clicking on one of the buttons in the right side panel. Google will then show only the selected result type – filtering out all others.

Search facets makes Google simple to use: You just enter a search string and click search.

You do not have to choose what kind of results you are looking for before you get the actual results.

## Understanding the WordPress search

The anatomy of a WordPress search is simple. When you enter the search string foobar and click search, WordPress requests the URL `/?s=foobar`.

Every time WordPress receives a request where the key `s` is set to some value the request is treated as a search and redirected to the result page.

### The `wp_query` object

The results is fetched from the data layer using the object ``wp_query``. The `wp_query` maintains a collection of predefined search conditions in key/value collection called query variables. The collection is manipulated by the two methods `set` and `get`.

The `wp_query` works by example: Most predefined query variables mirror the properties of a WordPress page. If you e.g. wish to fetch all pages with the title `Frontpage` you set the query variable `post_title` to `Frontpage`.

But the `wp_query` also includes some special query variables not directly related to page properties. The search uses its own variable `s`w to do full text searches. After this variable has been set the search continues and calls the method `get_post`.

The method `get_posts` execute the following steps:

1. Cleans and sanitizes the query variable by calling the method `parse_query`.
2. Fires the WordPress event `pre_get_posts`.
3. Translate the query variables into a SQL `SELECT` query.
4. Execute the SQL query and fetch the result. The database interaction is done by the data abstraction object `wpdb`.

## Implementing faceted search in WordPress

![UML facets](http://blog.kjeldby.dk/wp-content/uploads/uml-facet-hierarchy.png "UML facets")

My plugin consist of two parts: A widget and a Filter. The widget shows a list of all facets on the search result page:

Each facet is identified by an id, and when the user click a facets this id is added to the query string of a new search `/s=foobar&facet_id=4`.

Hence my facets are not filters but instead extra conditions that are added to the `wp_query` before performing a second search: It is most cases it is simply faster to let the database do another search than to start filtering the existing results in PHP.

### The signature of a facet

I have implemented three types of facet – but their all inherit from the same base class:

What differentiates the three types are the `add_facet` method. It takes the `wp_query` and the key/value pair to it – but in slightly different ways.

#### Pages where a property has a specific value

The most basic type of facet simply takes the key/value pair and add it to the list of query variables:

```
class Property_Facet extends Facet
{
    /* (non-PHPdoc)
    * @see Facet::add_facet()
    */
    public function add_facet(&$wp_query)
    {
        // Adding value to wp_query
        $wp_query->set($this->key, $this->value);
    }
}
```

This type can be used to create a many different facets:

```
$a = new Property_Facet();
$a->key = 'post_type';
$a->value = 'post';
```
 
```
$b = new Property ();
$b->key = 'post_parent';
$b->value = '230';
```
 
```
$c = new Property ();
$c->key = 'post_author';
$c->value = '25';
```

Facet `$a` shows only blog posts, facet `$b` shows only child pages of a specific page and facet `$c` shows only content from a specific author.

##### Understanding `post_type` property

WordPress’s two types of content – pages and posts – are both stored in the `wp_posts` table. The `post_type` property is used to differentiate the two.

When WordPress introduced custom types (e.g. `post_type='article'` or `post_type='record'`) a set of method was needed that would work on all post types. The result is a rather confusing API where the term post can mean content with `post_type='post'` as well as all kind of content in the `wp_posts` table.

#### Pages that have a specific set of metadata

Besides having properties a WordPress page has a key/value collection of custom meta data. My other type of facet adds conditions to this collection:

```
class Meta_Facet extends Facet
{
    /* (non-PHPdoc)
    * @see DVM_Facets_Facet::add_facet()
    */
    public function add_facet(&$wp_query)
    {
        $meta_query = $wp_query->get('meta_query');
        $meta_query[] =
            array
            (
                'key' => $this->key,
                'value' => $this->value,
                'compare' => 'LIKE'
            );
        $wp_query->set('meta_query', $meta_query);
    }
}
```

I use this type to create a facet that show only pages with a specific template:

```
$d = new Meta_Facet();
$d->key = '_wp_page_template';
$d->value = 'article.php';
```

#### Pages that descend from a specific page

The last type of facet shows only descendants of a specific page. I use this kind of facets to show results from different sections of my page.

To do this I need to get a list of all the descendants ids. Then I add this list to the query variable `post__in`. This narrows down the search to only the list of descendants.

So a ancestor facet is much like a property facet – we just need the list of ids. So I extent the property facet and inject a data access object that can create the list:

```
class Ancestor_Facet extends Property_Facet
{
    public $ancestor_id;
    public $dal;

    /**
    * Constructs a new facet
    * @param int $id Facet id
    * @param string $name Facet name
    * @param string $icon Facet icon
    * @param int $ancestor_id Ancestor post id
    * @param DAL $dal Data access layer
    */

    public
    function __construct($id, $name, $icon, $ancestor_id, $dal)
    {
        $this->ancestor_id = $ancestor_id;
        $this->dal = $dal;
        parent::__construct($id, $name, $icon, 'post__in', '');
    }

    /* (non-PHPdoc)
    * @see Property_Facet::add_facet()
    */
    public function add_facet(&$wp_query)
    {
        $this->value =
            $this->dal->get_descendant_ids($this->ancestor_id);
        parent::add_facet($wp_query);
    }
}
```

## Applying the facet

The next part of the puzzle is the mechanism that add the facet to the search when the request `/s=foobar&facet_id=4` is made. This is done by the `Filter` with is hooked up to the `pre_get_posts` event and hence run each time the `wp_query` is about to fetch pages.

```
class Filter
{
    /**
    * Applies any selected facets to the wp_query
    * @param wp_query $wp_query
    */
    function on_pre_get_posts(&$wp_query)
    {

        if ($wp_query->is_search())
        {
            $facet_query = new DVM_Facets_Facet_Query();
            $facet_query->
                from_query_string($_SERVER['QUERY_STRING']);
            if ($facet_query->has_facet_id())
            {
                $dal = new DVM_Facets_Dal();
                $facet = $dal->
                get_facet($facet_query->facet_id);
                $facet->add_facet($wp_query);
            }
        }
    }
}
```

The `wp_query` is used extensively in WordPress but most of the time the Filter does not do anything either because the `wp_query` is not prepared for a search or because no facets are specified in the query string.

But when a search is done and a facet is requested the `Filter` gets the facet and then add it to the `wp_query`.

The filter uses a `Facet_Query` to interact with the query string. This object encapsulates the query string and the parsing of it.

