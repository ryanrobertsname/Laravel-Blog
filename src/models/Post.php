<?php namespace Fbf\LaravelBlog;

use Cviebrock\EloquentSluggable\SluggableInterface;
use Cviebrock\EloquentSluggable\SluggableTrait;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Post extends \Eloquent implements SluggableInterface{

	use SluggableTrait;
	use SoftDeletingTrait;

	protected $dates = ['deleted_at'];
	
	/**
	 * Status values for the database
	 */
	const DRAFT = 'DRAFT';
	const APPROVED = 'APPROVED';

	/**
	 * Name of the table to use for this model
	 * @var string
	 */
	protected $table = 'fbf_blog_posts';

	/**
	 * The prefix string for config options.
	 *
	 * Defaults to the package's config prefix string
	 *
	 * @var string
	 */
	protected $configPrefix = 'laravel-blog::';

	/**
	 * Used for Cviebrock/EloquentSluggable
	 * @var array
	 */
	protected $sluggable = array(
		'build_from' => 'title',
		'save_to' => 'slug',
		'separator' => '-',
		'unique' => true,
		'include_trashed' => true,
		'use_cache' => false
	);

	/**
	 * Query scope for "live" posts, adds conditions for status = APPROVED and published date is in the past
	 *
	 * @param $query
	 * @return mixed
	 */
	public function scopeLive($query)
	{
		return $query->where($this->getTable().'.status', '=', Post::APPROVED)
			->where($this->getTable().'.published_date', '<=', \Carbon\Carbon::now());
	}

	/**
	 * Query scope for posts published within the given year and month number.
	 *
	 * @param $query
	 * @param $year
	 * @param $month
	 * @return mixed
	 */
	public function scopeByYearMonth($query, $year, $month)
	{
		return $query->where(\DB::raw('DATE_FORMAT(published_date, "%Y%m")'), '=', $year.$month);
	}

	/**
	 * Returns a formatted multi-dimensional array, indexed by year and month number, each with an array with keys for
	 * 'month name' and the count / number of items in that month. For example:
	 *
	 *      array(
	 *          2014 => array(
	 *              01 => array(
	 *                  'monthname' => 'January',
	 *                  'count' => 4,
	 *              ),
	 *              02 => array(
	 *                  'monthname' => 'February',
	 *                  'count' => 3,
	 *              ),
	 *          )
	 *      )
	 *
	 * @return array
	 */
	public static function archives()
	{
		// Get the data
		$archives = self::live()
			->select(\DB::raw('
				YEAR(`published_date`) AS `year`,
				DATE_FORMAT(`published_date`, "%m") AS `month`,
				MONTHNAME(`published_date`) AS `monthname`,
				COUNT(*) AS `count`
			'))
			->groupBy(\DB::raw('DATE_FORMAT(`published_date`, "%Y%m")'))
			->orderBy('published_date', 'desc')
			->get();

		// Convert it to a nicely formatted array so we can easily render the view
		$results = array();
		foreach ($archives as $archive)
		{
			$results[$archive->year][$archive->month] = array(
				'monthname' => $archive->monthname,
				'count' => $archive->count,
			);
		}
		return $results;
	}

	/**
	 * To filter posts by a relationship, you should extend the Post class and define both the relationship and the
	 * scopeByRelationship() method in your subclass. See the package readme for an example.
	 *
	 * @param $query
	 * @param $relationshipIdentifier
	 * @throws Exception
	 */
	public function scopeByRelationship($query, $relationshipIdentifier)
	{
		throw new Exception('Extend this class and override this method according to your app\s requirements');
	}

	/**
	 * Returns the HTML img tag for the requested image type and size for this item
	 *
	 * @param $type
	 * @param $size
	 * @return null|string
	 */
	public function getImage($type, $size, array $attributes = array())
	{
		if (empty($this->$type))
		{
			return null;
		}
		$html = '<img src="' . $this->getImageSrc($type, $size) . '"';
		$html .= ' alt="' . $this->{$type.'_alt'} . '"';
		$html .= ' width="' . $this->getImageWidth($type, $size) . '"';
		$html .= ' height="' . $this->getImageHeight($type, $size) . '"';
		
		$html_attributes = '';
		if (!empty($attributes)) {
			$html_attributes = join(' ', array_map(function($key) use ($attributes) {
				if(is_bool($attributes[$key]))
				{
					return $attributes[$key] ? $key : '';
				}
				return "{$key}=\"{$attributes[$key]}\"";
			}, array_keys($attributes)));
		}
		
		return $html;
	}

	/**
	 * Returns the value for use in the src attribute of an img tag for the given image type and size
	 *
	 * @param $type
	 * @param $size
	 * @return null|string
	 */
	public function getImageSrc($type, $size)
	{
		if (empty($this->$type))
		{
			return null;
		}
		return $this->getImageConfig($type, $size, 'dir') . $this->$type;
	}

	/**
	 * Returns the value for use in the width attribute of an img tag for the given image type and size
	 *
	 * @param $type
	 * @param $size
	 * @return null|string
	 */
	public function getImageWidth($type, $size)
	{
		if (empty($this->$type))
		{
			return null;
		}
		$method = $this->getImageConfig($type, $size, 'method');

		// Width varies for images that are 'portrait', 'auto', 'fit', 'crop'
		if (in_array($method, array('portrait', 'auto', 'fit', 'crop')))
		{
			list($width) = $this->getImageDimensions($type, $size);
			return $width;
		}
		return $this->getImageConfig($type, $size, 'width');
	}

	/**
	 * Returns the value for use in the height attribute of an img tag for the given image type and size
	 *
	 * @param $type
	 * @param $size
	 * @return null|string
	 */
	public function getImageHeight($type, $size)
	{
		if (empty($this->$type))
		{
			return null;
		}
		$method = $this->getImageConfig($type, $size, 'method');

		// Height varies for images that are 'landscape', 'auto', 'fit', 'crop'
		if (in_array($method, array('landscape', 'auto', 'fit', 'crop')))
		{
			list($width, $height) = $this->getImageDimensions($type, $size);
			return $height;
		}
		return $this->getImageConfig($type, $size, 'height');
	}

	/**
	 * Returns an array of the width and height of the current instance's image $type and $size
	 *
	 * @param $type
	 * @param $size
	 * @return array
	 */
	protected function getImageDimensions($type, $size)
	{
		$pathToImage = public_path($this->getImageConfig($type, $size, 'dir') . $this->$type);
		if (is_file($pathToImage) && file_exists($pathToImage))
		{
			list($width, $height) = getimagesize($pathToImage);
		}
		else
		{
			$width = $height = false;
		}
		return array($width, $height);
	}

	/**
	 * Returns the config setting for an image
	 *
	 * @param $imageType
	 * @param $size
	 * @param $property
	 * @internal param $type
	 * @return mixed
	 */
	public function getImageConfig($imageType, $size, $property)
	{
		$config = $this->getConfigPrefix().'images.' . $imageType . '.';
		if ($size == 'original')
		{
			$config .= 'original.';
		}
		elseif (!is_null($size))
		{
			$config .= 'sizes.' . $size . '.';
		}
		$config .= $property;
		return \Config::get($config);
	}

	/**
	 * Helper function to determine whether the item has a YouTube video
	 *
	 * @return bool
	 */
	public function hasYouTubeVideo()
	{
		return !empty($this->you_tube_video_id);
	}

	/**
	 * Returns the thumbnail image code defined in the config, for the current item's you tube video id
	 *
	 * @return string
	 */
	public function getYouTubeThumbnailImage()
	{
		return str_replace('%YOU_TUBE_VIDEO_ID%', $this->you_tube_video_id, \Config::get($this->getConfigPrefix().'you_tube.thumbnail_code'));
	}

	/**
	 * Returns the embed code defined in the config, for the current item's you tube video id
	 *
	 * @return string
	 */
	public function getYouTubeEmbedCode()
	{
		return str_replace('%YOU_TUBE_VIDEO_ID%', $this->you_tube_video_id, \Config::get($this->getConfigPrefix().'you_tube.embed_code'));
	}

	/**
	 * Returns the published date formatted according to the config setting
	 * @return string
	 */
	public function getDate()
	{
		return date(\Config::get($this->getConfigPrefix().'views.published_date_format'), strtotime($this->published_date));
	}

	/**
	 * Returns the URL of the post
	 * @return string
	 */
	public function getUrl()
	{
		if (\Config::get($this->getConfigPrefix().'routes.view_uri_date_prefix'))
		{
			return \URL::action('Fbf\LaravelBlog\PostsController@view', array('year' => $this->getYear(), 'month' => $this->getMonth(), 'slug' => $this->slug));
		}
		else
		{	
			return \URL::action('Fbf\LaravelBlog\PostsController@viewBySlug', array('slug' => $this->slug));
		}
	}

	/**
	 * Help function to determine whether the item has a link
	 * @return bool
	 */
	public function hasLink()
	{
		return !empty($this->link_text) && !empty($this->link_url);
	}

	/**
	 * Returns the next newer post, relative to the current one, if it exists
	 * @return mixed
	 */
	public function newer()
	{
		return $this->live()
			->where('published_date', '>=', $this->published_date)
			->where('id', '<>', $this->id)
			->orderBy('published_date', 'asc')
			->orderBy('id', 'asc')
			->first();
	}

	/**
	 * Returns the next older post, relative to the current one, if it exists
	 * @return mixed
	 */
	public function older()
	{
		return $this->live()
			->where('published_date', '<=', $this->published_date)
			->where('id', '<>', $this->id)
			->orderBy('published_date', 'desc')
			->orderBy('id', 'desc')
			->first();
	}

	/**
	 * Returns the config prefix
	 *
	 * @return string
	 */
	public function getConfigPrefix()
	{
		return $this->configPrefix;
	}

	/**
	 * Sets the config prefix string
	 *
	 * @param $configBase string
	 * @return string
	 */
	public function setConfigPrefix($configBase)
	{
		return $this->configPrefix = $configBase;
	}

	/**
	 * Return the published year
	 * 
	 * @return string
	 */
	public function getYear()
	{
		return date('Y', strtotime($this->published_date));
	}

	/**
	 * Return the published month
	 * 
	 * @return string
	 */
	public function getMonth()
	{
		return date('m', strtotime($this->published_date));
	}

	/**
	 * Overloads SluggableTrait method
	 * 
	 * @param $slug
	 * @return string
	 */
	protected function makeSlugUnique($slug)
	{
		if (!$this->sluggable['unique']) return $slug;

		if (\Config::get($this->getConfigPrefix().'routes.view_uri_date_prefix'))
		{
			if (empty($this->published_date))
			{
				throw new \RuntimeException('Published date is not set, cannot check slug uniqueness.');
			}

			$fully_qualified_slug = $this->getYear().'/'.$this->getMonth().'/'.$slug;
		}
		else
		{

			$fully_qualified_slug = $slug;
		}

		$separator  = $this->sluggable['separator'];
		$use_cache  = $this->sluggable['use_cache'];
		$save_to    = $this->sluggable['save_to'];

		// if using the cache, check if we have an entry already instead
		// of querying the database
		if ( $use_cache )
		{
			$increment = \Cache::tags('sluggable')->get($fully_qualified_slug);
			if ( $increment === null )
			{
				\Cache::tags('sluggable')->put($fully_qualified_slug, 0, $use_cache);
			}
			else
			{
				\Cache::tags('sluggable')->put($fully_qualified_slug, ++$increment, $use_cache);
				$slug .= $separator . $increment;
			}
			return $slug;
		}

		// no cache, so we need to check directly
		// find all models where the slug is like the current one
		$list = $this->getExistingSlugs($slug);

		// if ...
		// 	a) the list is empty
		// 	b) our slug isn't in the list
		// 	c) our slug is in the list and it's for our model
		// ... we are okay
		if (
			count($list)===0 ||
			!in_array($slug, $list) ||
			( array_key_exists($this->getKey(), $list) && $list[$this->getKey()]===$slug )
		)
		{
			return $slug;
		}


		// map our list to keep only the increments
		$len = strlen($slug.$separator);
		array_walk($list, function(&$value, $key) use ($len)
		{
			$value = intval(substr($value, $len));
		});

		// find the highest increment
		rsort($list);
		$increment = reset($list) + 1;

		return $slug . $separator . $increment;

	}

	/**
	 * Overloads SluggableTrait method
	 * 
	 * @param $slug
	 * @return array
	 */
	protected function getExistingSlugs($slug)
	{
		$save_to         = $this->sluggable['save_to'];
		$include_trashed = $this->sluggable['include_trashed'];

		$instance = new static;

		$query = $instance->where( $save_to, 'LIKE', $slug.'%' );

		// if posts are prefixed by pub date
		if (\Config::get($this->getConfigPrefix().'routes.view_uri_date_prefix'))
		{
			$query = $query->where(\DB::raw('DATE_FORMAT(published_date, "%Y%m")'), '=', $this->getYear().$this->getMonth());
		}

		// include trashed models if required
		if ( $include_trashed && $instance->usesSoftDeleting() )
		{
			$query = $query->withTrashed();
		}

		// get a list of all matching slugs
		$list = $query->lists($save_to, $this->getKeyName());

		return $list;
	}
}
