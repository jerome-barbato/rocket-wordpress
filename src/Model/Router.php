<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Rocket\Model;

use Symfony\Component\Routing\Matcher\UrlMatcher,
    Symfony\Component\Routing\RequestContext,
    Symfony\Component\Routing\RouteCollection;

class Router {


    protected $routes, $locale, $errors;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }


    /**
     * Set locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }


    /**
     * Get current url path
     * @return string
     */
    private function get_current_url()
    {
        $current_url = ltrim(esc_url_raw(add_query_arg([])), '/');

	    $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
	    if ($home_path && strpos($current_url, $home_path) === 0)
		    $current_url = ltrim(substr($current_url, strlen($home_path)), '/');

	    $query_var_pos = strpos($current_url, '?');

	   if( $query_var_pos === false )
		   return '/'.$current_url;
	   else
		   return '/'.substr($current_url, 0, $query_var_pos);
    }


	/**
	 * Get ordered paramerters
	 * @return array
	 */
	private function getClosureArgs( $func ){

		$closure    = &$func;
		$reflection = new \ReflectionFunction($closure);
		$arguments  = $reflection->getParameters();

		$args = [];

		foreach ($arguments as $arg)
			$args[] = $arg->getName();

		return $args;
	}


    /**
     * Define route manager
     * @return bool|mixed
     */
    public function solve()
    {
        $current_url = $this->get_current_url();

        $request_context = new RequestContext('/');
        $matcher = new UrlMatcher($this->routes, $request_context);

        $resource = $matcher->match($current_url);

        if( $resource and isset($resource['_controller']) )
        {
            $controller = $resource['_controller'];
            $args = $this->getClosureArgs($controller);

	        $resource['locale'] = $this->locale;

	        $params = [];

            foreach ($args as $arg)
	            $params[] = isset($resource[$arg])?$resource[$arg]:null;

            return call_user_func_array($controller, $params);
        }
        else
            return false;
    }

    /**
     * Define route manager
     * @param $pattern
     * @param $controller
     * @return Route
     */
    public function add($pattern, $controller)
    {
    	if( is_int($pattern) )
	    {
		    $this->errors[$pattern] = $controller;
	    }
	    else
	    {
		    $route = new Route($pattern, ['_controller' => $controller]);
		    $name  = $this->generateRouteName($route);

		    $this->routes->add($name, $route);

		    return $route;
	    }
    }

	/**
	 * Define error manager
	 * @param $code
	 * @return Route
	 * @internal param $pattern
	 * @internal param $controller
	 */
    public function error($code)
    {
	    if( isset($this->errors[$code] ) )
	    {
		    $controller = $this->errors[$code];
		    return call_user_func_array($controller, [$this->locale]);
	    }
	    else
	    	return false;
    }


    /**
     * Generate route name from pattern
     * @param $route
     * @param string $prefix
     * @return mixed|string
     */
    private function generateRouteName($route, $prefix='')
    {
        $methods = implode('_', $route->getMethods()).'_';

        $routeName = $methods.$prefix.$route->getPath();
        $routeName = str_replace(array('/', ':', '|', '-'), '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        // Collapse consecutive underscores down into a single underscore.
        $routeName = preg_replace('/_+/', '_', $routeName);

        return $routeName;
    }

}
