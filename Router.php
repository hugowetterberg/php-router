<?php
/*
    Copyright 2009 Rob Apodaca

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
abstract class Router
{
    /**
     *
     * @var array
     */
    private static $routeMaps = array();

    /**
     *
     * @var string
     */
    private static $routesFilePath = '../config/routes.php';

    /**
     *
     * @param string $path
     * @param array $map
     * @throws Exception
     */
    public static function addRoute($path, $map)
    {
        //verify that all array keys exist
        if(!isset($map['controller'])) throw new Exception("key 'controller' not found when adding route");
        if(!isset($map['action'])) throw new Exception("key 'action' not found when adding route");
        if(!isset($map['args'])) throw new Exception("key 'args' not found when adding route");

        //eg $path = '/<controller>/:action/:id'
        self::$routeMaps[$path] = $map;
    }

    /**
     * @desc Auto Load routes using Router::$routesFilePath
     */
    public static function autoLoadRoutes( $path = NULL )
    {
        if( NULL !== $path )
            self::$routesFilePath = $path;

        require_once( self::$routesFilePath );

        try
        {
            foreach( $routes as $path => $map )
            {
                self::addRoute($path, $map);
            }
        }
        catch( Exception $e)
        {
            die('route map error while trying to auto load ' . $e->getMessage());
        }
    }

    /**
     * @desc enables the class autoloader
     */
    public static function enableClassAutoLoader()
    {
        spl_autoload_register('Router::classAutoLoader' );
    }

    /**
     * @desc Register this static method with spl_autoload_register
     * @param string $class
     */
    public static function classAutoLoader( $class )
    {
        @include_once($class . '.php');
    }

    /**
     * @desc maps the current url request to any or sets header 404
     */
    public static function mapUrlRequest()
    {
        $url = ereg_replace('^/', '', $_SERVER['REQUEST_URI']);
        if( FALSE === self::matchMap($url) )
            header("HTTP/1.0 404 Not Found");
    }

    /**
     * @desc finds a maching route in the map using path
     * @param string $path
     * @return boolean
     */
    public static function matchMap( $path )
    {
        $path_parts = split('/', $path);
        $path_parts_count = count($path_parts);

        //It may be a static map, check first by matching the $path verbatim
        if( array_key_exists($path, self::$routeMaps) )
        {
            //This is a possible static routeMap, to be sure,
            // check the count of the args sub array
            if( 0 === count(self::$routeMaps[$path]['args']) )
            {
                return self::dispatch(
                        self::$routeMaps[$path]['controller'],
                        self::$routeMaps[$path]['action'],
                        self::$routeMaps[$path]['args']
                );
            }

        }

        //Above, we've already checked for matching static maps, so if this point
        // is reached, the request map is not a matched static map - although
        // it could be an unmatched static map
        foreach( self::$routeMaps as $route_map_path => $route_map )
        {
            //Any static maps here are ignored because any match would have
            // been caught earlier, so empty args arrays are static and must
            // be ignored.
            $args = $route_map['args'];
            if( 0 === count($args) )
                continue;

            //In order to be a possible match, the array counts must
            // be equal
            $route_map_path_parts = explode('/', $route_map_path);
            if( count($route_map_path_parts) !== count($path_parts) )
                continue;

            //At this point, we still need to determine if any and all static
            // elements of the path match up. Then, the dynamic elements
            // can be matched.
            $dynamic_route_elements = array();
            $static_route_elements = array();
            foreach( $route_map_path_parts as $k => $route_element )
            {
                if( ':' === $route_element[0] )
                    $dynamic_route_elements[$k] = $route_element;
                else
                    $static_route_elements[$k] = $route_element;
            }

            $static_element_match = TRUE;
            foreach( $static_route_elements as $k => $static_route_element )
            {
                if( $static_route_element !== $path_parts[$k] )
                {
                    //if any static elements do not match, then the matche fails
                    $static_element_match = FALSE;
                    break;
                }
            }
            if( FALSE === $static_element_match )
                continue;

            //Now check any dynamic elements for matches
            $dynamic_element_match = TRUE;
            $args_match = array();
            foreach( $dynamic_route_elements as $k => $dynamic_route_element )
            {
                 $regexp = $args[$dynamic_route_element];

                 //NULL regexp specfied in the map match anything
                 if( NULL === $regexp )
                 {
                    $args_match[$route_map_path_parts[$k]] = $path_parts[$k];
                    continue;
                 }

                 //Any regexp which does not match, results in a total failure
                 // of the current iteration
                 if( preg_match($regexp, $path_parts[$k]) > 0 )
                 {
                    $args_match[$route_map_path_parts[$k]] = $path_parts[$k];
                 }
                 else
                 {
                     $dynamic_element_match = FALSE;
                     break;
                 }
            }
            if( FALSE === $dynamic_element_match )
                continue;

            //if this point has been reached in the loop, a match (the first
            // match to be exact) has been found. Route the request
            $action_array = array();
            foreach( $route_map as $k => $route_map_element )
            {
                if( 'args' === $k )
                {
                    foreach( $route_map_element as $kk => $v )
                    {
                        $action_array[$k][$kk] = $args_match[$kk];
                    }
                }
                else
                {
                    $action_array[$k] = $route_map_element;
                }
            }

            //if the current iteration of the loop reaches here, it could only
            // be a match, so simply break to prevent further matching
            break;

        }

        if( count($action_array) > 0 )
        {
            //there may be elments other than arguments which are dynamic
            // find those and replace with the correct values
            foreach( $action_array as $k => $v )
            {
                if( FALSE === is_array($v) && TRUE === array_key_exists($v, $action_array['args']) )
                {
                    $action_array[$k] = $action_array['args'][$v];
                }
            }

            return self::dispatch(
                    $action_array['controller'],
                    $action_array['action'],
                    $action_array['args']
            );
        }
        else
        {

            return FALSE;
        }
    }

    /**
     * @desc dispatches controller, action, args
     * @param string $controller
     * @param string $action
     * @param array $args
     * @return boolean
     */
    private static function dispatch( $controller, $action, $args )
    {
        if( class_exists($controller) )
        {
            $c = new $controller;

            if( method_exists($c, $action))
                $c->$action($args);
            else
                return FALSE;
        }
        else
        {
            return FALSE;
        }
        return TRUE;
    }
}
?>
