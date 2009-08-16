<?php
include_once(dirname(__FILE__) . '/Route.php');
include_once(dirname(__FILE__) . '/Dispatcher.php');

/**
 * @author Rob Apodaca <rob.apodaca@gmail.com>
 * @copyright Copyright (c) 2009, Rob Apodaca
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://robap.github.com/php-router/
 */
class Router
{
    /**
     * Stores the Route objects
     * @var array
     * @static
     * @access private
     */
    private static $_routes = array();

    /**
     * Private constructor ensures no instances
     */
    private function __construct(){}
    
    /**
     * Adds a named route to the list of possible routes
     * @param string $name
     * @param Route $route
     * @static
     * @access public
     * @return void
     */
    public static function addRoute( $name, &$route )
    {
        self::$_routes[$name] = $route;
    }

    /**
     * Returns the routes array
     * @return array
     * @static
     * @access public
     */
    public static function getRoutes()
    {
        return self::$_routes;
    }

    /**
     * Builds and gets a url for the named route
     * @param string $name
     * @param array $args
     * @return string the url
     */
    public static function getUrl( $name, $args = array() )
    {
        if( TRUE === isset(self::$_routes[$name]) )
        {
            $match_ok = TRUE;

            //Check for the correct number of arguments
            if( count($args) !== count(self::$_routes[$name]->getDynamicElements()) )
                $match_ok = FALSE;

            $path = self::$_routes[$name]->getPath();
            foreach( $args as $arg_key => $arg_value )
            {
                $path = str_replace( $arg_key, $arg_value, $path, $count );
                if( 1 !== $count )
                    $match_ok = FALSE;
            }

            //Check that all of the argument keys matched up with the dynamic elements
            if( FALSE === $match_ok )
                trigger_error('Incorrect arguments for named path');

            return $path;
        }
        else
        {
            trigger_error('Named Path not found in Router');
            return '';
        }
    }

    /**
     * Finds a maching route in the routes array using specified $path
     * @param string $path
     * @return mixed Route/Boolean
     * @static
     * @access public
     */
    public static function findRoute( $path )
    {
        $found_route = FALSE;

        foreach( self::$_routes as $route )
        {
            if( TRUE === $route->matchMap( $path ) )
            {
                $found_route = $route;
                break;
            }
        }

        return $found_route;
    }
    
    /**
     * Resets the class (mainly used for testing)
     * @return void
     * @access public
     * @static
     */
    public static function resetRouter()
    {
        self::$_routes = array();
    }
}
?>
