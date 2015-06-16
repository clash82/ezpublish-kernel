## Overview

Named queries, or QueryTypes, are predefined Content or Location Query objects. They can be used in several ways:
- from twig templates, using a sub-request:
  `{{ render( controller( ez_query, {'identifier': 'latest_articles' ) ) }}`
- from php code, by loading the Query, and running it through the SearchService
- from REST, using `/content/views`

QueryTypes can accept parameters, that can be used as values in the query. It can be used for paging, or to set a
ContentType identifier...

In the initial version, QueryType objects can be created as PHP classes. The main use-case is to allow developers
to define Queries for the project that can be used in several ways, without knowing their internals.

### Differences from Persisted Queries

When `Views` were added to REST API there was the intention to allow these to be persisted at some point *(after UI were moved to Platform Stack)*.
And while there are no own specification for it yet, these are the main differences between the two:
- Persisted Queries serves as Editorial Queries/Search to drive possible future features like reuse in content, blocks, and plainly sharing link to a search result to co workers or public.
- Named Queries on the other side is a DX feature for developer to provided queries for reuse in code, and aim to make eZ Platform much easier to work with by exposing the power of `filtering` content directly from templates.
- Persisted Queries, unlike Named Queries, will by it's nature be possible to be retrieve and created from PHP Repository API, and hence also REST API which will need to distinguishing these two from each other for API use and in extension UI's exposing such editing functionality.
- Persisted Queries could allow the Repository to keep track of hits internally in the future, ala how JIRA works with filters. This will allow more advance cache clearing logic to aim for editor never having to take care about cache.
- Persisted Queries being editorial and not a DX feature like Named Query, meaning editor might change it completely at any moment, and also for cache reasons mentioned above this means it most likely won't support dynamic parameters beyond limit, offset and viewMode *(does not belong to Query, rather view and would be exposed by embeds and blocks)*.

However Persisted Queries are a non-goal of this spec and feature, this section is merely to make it's relation more clear as there are many possible overlaps in featureset for Queries and Views.

## Components

### REST

#### Getting a view's results
Returns the same results that POSTing a Query to `/content/views`,  but without the need to POST the Query. Results can
also be cached over HTTP.

```
GET /api/ezp/v2/content/views/AcmeBundle:LatestArticles
```

##### Passing parameters
QueryTypes/Views can define custom parameters, optional or not, that can be given a custom value.

Over REST, View arguments are passed as query parameters:
```
GET /api/ezp/v2/content/views/AcmeBundle:LatestArticles?category=marketing
```

In the example above, category could be mapped to a Location ID, or a Content Field, either tag, or selection,
or object relation...


### Query controller

> Status: must have

```twig
{{ render( 
	controller( 
		ez_query, 
		{
			'identifier': 'AcmeBundle:LatestArticles',
			'parameters': {'category': 'marketing'}
		} 
	) 
) }}
```

### Services

#### Main QueryTypeRegistry

> Status: must have

A main `QueryTypeRegistry` / `ezplatform.query_type.registry`, with a `load` method that accepts a QueryType identifier.

(REST: No store, delete or update methods as persistence will be handled later)

#### Per query service

> Status: questionable (naming is an issue if we want to follow namespacing)

Automatically generated services (compiler pass), per query: `ezplatform.query_type.latest_articles`
(How do we get from `AcmeBundle:LatestArticles` to a usable service name ? text transformation ?)

## QueryType object

QueryTypes are PHP classes that implement the `QueryType` interface, and are registered into the service container.

### QueryType identifiers
QueryTypes are identified using the bundle's name and the QueryType's name itself. This is done to prevent conflicts between QueryTypes from different bundles.

Example: `AcmeBundle:LatestArticles`

### Service container registration

#### Service tag
> tag name: `ez_query`

The service alias depends on the class and on the bundle.

#### Naming convention
In a bundle, a file named `eZ/QueryType/{Something}QueryType.php`, with a matching class name, will be registered and tagged as an eZ Query.

### Interface
```php
/**
 * A QueryType is a registered Repository Search Query.
 */
interface QueryType
{
  /**
   * Builds and returns the Query object
   * @param array $parameters query parameters, if any was defined in the QueryType
   * @return \eZ\Publish\API\Repository\Values\Content\Query
   */
  public function getQuery( array $parameters = [] );
}
```

#### Parameters
QueryTypes that support parameters must implement the NAME-ME interface. It has a `getDefinedParameters()` that returns an array.

```php
interface ConfigurableQueryType
{
    /**
     * Returns the list of defined parameters for the QueryType
     * @return array
     */
    public function getDefinedParameters();
}
```

> Q: what about required/optional parameters ? what about parameters types (array, scalar mostly) ?

#### Example: latest articles

```php
class LatestArticlesQueryType implements LocationQueryType
{
    public function getQuery( array $parameters = [] )
    {
        $query = new LocationQuery();
        $query->criterion = new Criterion\LogicalAnd(
            [
                $criteria[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );
                criteria[] = new Criterion\ContentTypeIdentifier( 'article' );
				// Could come from siteaccess aware config
				criteria[] = new Criterion\ParentLocationId(42);
            ]
        );
        $query->sortClauses = [new SortClause\DatePublished()];
        $query->limit = 10;
        return $query;
    }
}
```

## Ideas and open questions

### Expression language
It could be used as a shortcut to get the Query itself, optionally.

### Query types
How do we distinguish/handle Location vs Content queries ?

SubClass the main QueryType as LocationQueryType and ContentQueryType ? The REST API distinguishes them as follows:
- The XML/JSON node representing the Query is either a LocationQuery or a ContentQuery
- The result with either contain Content or Location objects (the endpoint chooses the right Search method depending on the type of Query.

### Caching
If we want REST views to be cacheable, we should add the relevant X-Location-ID headers in the result.

> Q: Is it something we want ?

### Dependency on REST LocationSearch
LocationQueries will obviously not work until LocationSearch support is added to REST.

### Should we separate content & location search queries ?

On second thought, having one feature handling both queries (over REST and PHP) could get tedious.
Maybe not so much over REST, since you will get the ContentType, and the Query has already been executed.

Another way would be to distinguish the registry's methods: `QueryTypeRegistry::loadContentQuery`, or `QueryTypeRegistry::loadLocationQuery`.
But this would kind of imply *two* registries.

## What about View objects in PHP ?

For PHP developers, you'd have to test what kind of query you got, in order to send it to the right SearchService
method. To be on par with REST, one way would be to add a View object.

Either it's a Value object that contains the query, and can execute itself, or a dedicated service, that can be given a
Query, and will be returned the results. And if the View was an iterator, it could really make a few things easier.

Though you'd still have to test what the query contains before you can use it, but you also have to do this with REST.

Or we just need separate `LocationView` and `ContentView` objects in PHP as well. But then, how do you load those views ?
Then maybe we instead need a ViewTypeRegistry instead of a QueryTypeRegistry.

### Usage example

```php
$view = new View(
    $queryTypeRegistry->get( 'AcmeBundle:latestArticles` )
);

$viewResult = $view->execute( ['category' => 'marketing'] );
foreach ( $viewResult as $result )
{
  // ...
}
```

### View objects

#### View

A View is a stateless QueryType container. It can execute the query, with or without parameters, and returns a ViewResult.

> Q: what is the point/role of this object, again ? Especially since it needs to be built...

```php
interface View
{
    /**
     * @return Query
     */
    public function getQuery();

    /**
     * @return ViewResult
     */
    public function execute( array $parameters = [] );
}
```

#### ViewResult

A ViewResult contains the results of the execution of a view. It acts as a proxy to the the API's SearchResult object.
It may provide automatic paging of results by automatically managing the offset/limit Query Parameters (this is something
the View object can not do, as it is stateless).


```
interface ViewResult implements Countable, Iterator
{
}
```

> Q: what is the link between a ViewResult and its View ?
> Q: does the ViewResult add anything to the existing SearchResult ?
