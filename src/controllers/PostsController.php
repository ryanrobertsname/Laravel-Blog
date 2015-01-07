<?php namespace Fbf\LaravelBlog;

class PostsController extends \BaseController {

	/**
	 * @var \Fbf\LaravelBlog\Post
	 */
	protected $post;

	/**
	 * @param \Fbf\LaravelBlog\Post $post
	 */
	public function __construct(Post $post)
	{
		$this->post = $post;
	}

	/**
	 * @return mixed
	 */
	public function index()
	{
		// Get the selected posts
		$posts = $this->post->live()
			->orderBy($this->post->getTable().'.is_sticky', 'desc')
			->orderBy($this->post->getTable().'.published_date', 'desc')
			->paginate(\Config::get('laravel-blog::views.index_page.results_per_page'));

		// Get the archives data if the config says to show the archives on the index page
		if (\Config::get('laravel-blog::views.index_page.show_archives'))
		{
			$archives = $this->post->archives();
		}

		return \View::make(\Config::get('laravel-blog::views.index_page.view'), compact('posts', 'archives'));
	}

	/**
	 * @param $selectedYear
	 * @param $selectedMonth optional
	 * @param $selectedDay optional
	 * @return mixed
	 */
	public function indexByDate($selectedYear, $selectedMonth = null, $selectedDay = null)
	{
		// Get the selected posts
		$query = $this->post->live();
			if ($selectedDay)
			{	
				$query->byYearMonthDay($selectedYear, $selectedMonth, $selectedDay);
			}
			else if ($selectedMonth)
			{
				$query->byYearMonth($selectedYear, $selectedMonth);
			}
			else
			{
				$query->byYear($selectedYear);
			}

		$posts = $query->orderBy($this->post->getTable().'.is_sticky', 'desc')
			->orderBy($this->post->getTable().'.published_date', 'desc')
			->paginate(\Config::get('laravel-blog::views.index_page.results_per_page'));

		// Get the archives data if the config says to show the archives on the index page
		if (\Config::get('laravel-blog::views.index_page.show_archives'))
		{
			$archives = $this->post->archives();
		}

		return \View::make(\Config::get('laravel-blog::views.index_page.view'), compact('posts', 'selectedYear', 'selectedMonth', 'archives'));
	}

	/**
	 * @param $relationshipIdentifier
	 * @return mixed
	 */
	public function indexByRelationship($relationshipIdentifier)
	{
		// Get the selected posts
		$posts = $this->post->live()
			->byRelationship($relationshipIdentifier)
			->orderBy($this->post->getTable().'.is_sticky', 'desc')
			->orderBy($this->post->getTable().'.published_date', 'desc')
			->paginate(\Config::get('laravel-blog::views.index_page.results_per_page'));

		// Get the archives data if the config says to show the archives on the index page
		if (\Config::get('laravel-blog::views.index_page.show_archives'))
		{
			$archives = $this->post->archives();
		}

		return \View::make(\Config::get('laravel-blog::views.index_page.view'), compact('posts', 'archives'));
	}

	/**
	 * @param $slug
	 * @return mixed
	 */
	public function viewWithSlug($slug)
	{
		return $this->view(null, null, null, $slug);
	}

	/**
	 * @param $year
	 * @param $slug
	 * @return mixed
	 */
	public function viewWithYear($year, $slug)
	{
		return $this->view($year, null, null, $slug);
	}

	/**
	 * @param $year
	 * @param $month
	 * @param $slug
	 * @return mixed
	 */
	public function viewWithMonth($year, $month, $slug)
	{
		return $this->view($year, $month, null, $slug);
	}

	/**
	 * @param $year
	 * @param $month
	 * @param $day
	 * @param $slug
	 * @return mixed
	 */
	public function viewWithDay($year, $month, $day, $slug)
	{
		return $this->view($year, $month, $day, $slug);
	}
	
	/**
	 * @param $slug
	 * @param $year
	 * @param $month
	 * @return mixed
	 */
	public function view($year = null, $month = null, $day = null, $slug)
	{
		$date_prefix_config = \Config::get('laravel-blog::routes.view_uri_date_prefix');

		// Get the selected post
		$query = $this->post->live()->where($this->post->getTable().'.slug', '=', $slug);
		if ($date_prefix_config == 'year')
		{
			$query = $query->where(\DB::raw('DATE_FORMAT(published_date, "%Y")'), '=', $year);
		}
		if ($date_prefix_config == 'month')
		{
			$query = $query->where(\DB::raw('DATE_FORMAT(published_date, "%Y%m")'), '=', $year.$month);
		}
		if ($date_prefix_config == 'day')
		{
			$query = $query->where(\DB::raw('DATE_FORMAT(published_date, "%Y%m%d")'), '=', $year.$month.$day);
		}
		$post = $query->firstOrFail();

		// Get the next newest and next oldest post if the config says to show these links on the view page
		$newer = $older = false;
		if (\Config::get('laravel-blog::views.view_page.show_adjacent_items'))
		{
			$newer = $post->newer();
			$older = $post->older();
		}

		// Get the archives data if the config says to show the archives on the view page
		if (\Config::get('laravel-blog::views.view_page.show_archives'))
		{
			$archives = $this->post->archives();
		}

		return \View::make(\Config::get('laravel-blog::views.view_page.view'), compact('post', 'newer', 'older', 'archives'));

	}

	/**
	 * @return mixed
	 */
	public function rss()
	{
		$feed = Rss::feed('2.0', 'UTF-8');
		$feed->channel(array(
			'title' => \Config::get('laravel-blog::meta.rss_feed.title'),
			'description' => \Config::get('laravel-blog::meta.rss_feed.description'),
			'link' => \URL::current(),
		));
		$posts = $this->post->live()
			->where($this->post->getTable().'.in_rss', '=', true)
			->orderBy($this->post->getTable().'.published_date', 'desc')
			->take(10)
			->get();
		foreach ($posts as $post){
			$feed->item(array(
				'title' => $post->title,
				'description' => $post->summary,
				'link' => \URL::action('Fbf\LaravelBlog\PostsController@view', array('slug' => $post->slug)),
			));
		}
		return \Response::make($feed, 200, array('Content-Type', 'application/rss+xml'));
	}

}
