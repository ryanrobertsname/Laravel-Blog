<?php

return array(

	/**
	 * Determines whether to load the package routes. If you want to specify them yourself in your own `app/routes.php`
	 * file then set this to false.
	 */
	'use_package_routes' => true,

	/**
	 * Base URI of the package's pages, e.g. "blog" in http://domain.com/blog and http://domain.com/blog/my-post
	 */
	'base_uri' => 'blog',

	/**
	 * Prefix View URI With Year And Month, e.g. "/2015/01/" in http://domain.com/blog/2015/01/my-post
	 */
	'view_uri_date_prefix' => false,

	/**
	 * URI prefix of the blog relationship filter
	 *
	 * @type mixed false | string
	 */
	'relationship_uri_prefix' => false,

);