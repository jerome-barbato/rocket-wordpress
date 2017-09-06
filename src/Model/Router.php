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


    protected $routes, $locale;

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
     * Define route manager
     * @return bool|mixed
     */
    public function solve()
    {
        $current_url = $this->get_current_url();

        $request_context = new RequestContext('/');
        $matcher = new UrlMatcher($this->routes, $request_context);

        if( isset($_GET['debug']) and $_GET['debug']=="route" )
	        echo '<!-- current url : '.$current_url.' -->';

        $parameters = $matcher->match($current_url);

        if( $parameters and isset($parameters['_controller']) )
        {
            $controller = $parameters['_controller'];
            $params = array_filter($parameters, function($key){ return substr($key,0,1) != '_'; }, ARRAY_FILTER_USE_KEY);
            array_unshift($params, $this->locale);

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
        $route = new Route($pattern, ['_controller' => $controller]);
        $name  = $this->generateRouteName($route);

        $this->routes->add($name, $route);

        return $route;
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
