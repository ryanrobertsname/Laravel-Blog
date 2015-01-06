<?php

// Main default listing e.g. http://domain.com/blog
Route::get(Config::get('laravel-blog::routes.base_uri'), ['as' => 'laravel-blog.index', 'uses' => 'Fbf\LaravelBlog\PostsController@index']);

// Archive (year / month) filtered listing e.g. http://domain.com/blog/yyyy/mm
Route::get(Config::get('laravel-blog::routes.base_uri').'/{year}/{month}', ['as' => 'laravel-blog.archive', 'uses' => 'Fbf\LaravelBlog\PostsController@indexByYearMonth'])->where(array('year' => '\d{4}', 'month' => '\d{2}'));

if (Config::get('laravel-blog::routes.relationship_uri_prefix'))
{
	// Relationship filtered listing, e.g. by category or tag, e.g. http://domain.com/blog/category/my-category
	Route::get(Config::get('laravel-blog::routes.base_uri').'/'.Config::get('laravel-blog::routes.relationship_uri_prefix').'/{relationshipIdentifier}', ['as' => 'laravel-blog.relationship', 'uses' => 'Fbf\LaravelBlog\PostsController@indexByRelationship']);
}

// Blog post detail variants based on date prefix config
if (\Config::get('laravel-blog::routes.view_uri_date_prefix') == 'year')
{
	// Blog post detail page e.g. http://domain.com/blog/2015/my-post
	Route::get(Config::get('laravel-blog::routes.base_uri').'/{year}/{slug}', ['as' => 'laravel-blog.viewWithYear', 'uses' => 'Fbf\LaravelBlog\PostsController@viewWithYear']);
}
else if (\Config::get('laravel-blog::routes.view_uri_date_prefix') == 'month') 
{
	// Blog post detail page e.g. http://domain.com/blog/2015/01/my-post
	Route::get(Config::get('laravel-blog::routes.base_uri').'/{year}/{month}/{slug}', ['as' => 'laravel-blog.viewWithMonth', 'uses' => 'Fbf\LaravelBlog\PostsController@viewWithMonth']);
}
else if (\Config::get('laravel-blog::routes.view_uri_date_prefix') == 'day') 
{
	// Blog post detail page e.g. http://domain.com/blog/2015/01/02/my-post
	Route::get(Config::get('laravel-blog::routes.base_uri').'/{year}/{month}/{day}/{slug}', ['as' => 'laravel-blog.viewWithDay', 'uses' => 'Fbf\LaravelBlog\PostsController@viewWithDay']);
}
else
{
	// Blog post detail page e.g. http://domain.com/blog/2015/01/my-post
	Route::get(Config::get('laravel-blog::routes.base_uri').'/{slug}', ['as' => 'laravel-blog.viewWithSlug', 'uses' => 'Fbf\LaravelBlog\PostsController@viewWithSlug']);	
}

// RSS feed URL e.g. http://domain.com/blog.rss
Route::get(Config::get('laravel-blog::routes.base_uri').'.rss', ['as' => 'laravel-blog.rss', 'uses' => 'Fbf\LaravelBlog\PostsController@rss']);