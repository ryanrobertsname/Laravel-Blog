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
	 * Prefix View URI With Year, Year & Month, or Year Month & Day, e.g. "/2015/01/02" in http://domain.com/blog/2015/01/02/my-post
	 *
	 * Options: 'year' , 'month' , 'day' , null
	 *
	 * Slug uniqueness will be considered with any date prefixing that is configured, 
	 * e.g. If this is set to 'year', slug "my-post" can be duplicated if posted in different years.
	 *
	 * WARNING: If you have existing posts, ensure you take necessary action in ensuring that slugs are appropriately
	 * unique upon changing this setting.
	 */
	'view_uri_date_prefix' => 'day',

	/**
	 * URI prefix of the blog relationship filter
	 *
	 * @type mixed false | string
	 */
	'relationship_uri_prefix' => false,

);