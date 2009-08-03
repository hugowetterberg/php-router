<?php
/*
    Copyright 2009 Rob Apodaca <rob.apodaca@gmail.com>

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

include_once(dirname(__FILE__) . '/Route.php');
include_once(dirname(__FILE__) . '/Dispatcher.php');

abstract class Router
{
    /**
     * Stores the Route objects
     * @var array
     * @static
     * @access private
     */
    private static $_routes = array();

    /**
     * Adds a route to the list of possible routes
     * @param Route $route
     * @static
     * @access public
     * @return void
     */
    public static function addRoute( &$route )
    {
        self::$_routes[] = $route;
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

    public static function resetRouter()
    {
        self::$_routes = array();
    }
}
?>
