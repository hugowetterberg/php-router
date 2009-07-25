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
abstract class Router
{
    /**
     * Stores the route maps
     * @var array
     * @static
     * @access private
     */
    private static $routeMaps = array();

    /**
     * The path to the controllers
     * @var string
     * @static
     * @access private
     */
    private static $controllerPath;

    /**
     * The controller suffix; i.e. '.class.php', '.php', etc
     * @var string
     * @access private
     * @static
     */
    private static $controllerSuffix = 'Controller.php';

    /**
     * Stores the requested path
     * @var string
     * @access private
     * @static
     */
    private static $requestedPath;

    /**
     * If set, stores an array with keys 'class' and 'method', else empty array.
     * Used to call a custom dispatcher with class::method( $dynamic )
     * @var array
     * @static
     * @access private
     */
    private static $customDispatcher = array();

    /**
     * If set, stores an array with keys 'class' and 'method', else empty array.
     * Used to call a custom uri error handler class::method
     * @var array
     */
    private static $customUriErrorHandler = array();

    /**
     * Stores error messages
     * @var array
     * @static
     * @access private
     */
    private static $errors = array();

    /**
     * Adds a route to the list of possible routes
     * @param string $path In the form: 'my/path'
     * @param array $map required keys: 'controller', 'action', 'dynamic'
     * @throws Exception
     * @static
     * @access public
     * @return void
     */
    public static function addRoute($path, $map)
    {
        //verify that all array keys exist
        if(!isset($map['controller'])) throw new Exception("key 'controller' not found when adding route");
        if(!isset($map['action'])) throw new Exception("key 'action' not found when adding route");
        if(!isset($map['dynamic'])) throw new Exception("key 'dynamic' not found when adding route");

        //eg $path = '/<controller>/:action/:id'
        self::$routeMaps[$path] = $map;
    }

    /**
     * Auto Load routes using $routes_file
     * @param string $routes_file
     * @static
     * @access public
     * @return void
     */
    public static function autoLoadRoutes( $routes_file )
    {
        require( $routes_file );

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
     * Finds a maching route in the map using path
     * @param string $path
     * @return boolean
     * @static
     * @access public
     */
    public static function matchMap( $path )
    {
        //remove any query portion of the path
        $initial_path_split = explode('?', $path);
        if( TRUE === isset($initial_path_split[0]) )
            $path = $initial_path_split[0];

        self::$requestedPath = $path;

        //Remove any preceding slash from the uri path. It would cause problems
        // when splitting later.
        $path = ereg_replace('^/', '', $path);

        //Split the path up so that each element can be worked on
        $path_parts = split('/', $path);
        $path_parts_count = count($path_parts);
        
        //There may be a static map for the path, check first by matching the
        // $path verbatim
        if( array_key_exists($path, self::$routeMaps) )
        {
            //This is a possible static routeMap, to be sure,
            // check the count of the dynamic sub array
            if( 0 === count(self::$routeMaps[$path]['dynamic']) )
            {
                return self::dispatch(
                        self::$routeMaps[$path]['controller'],
                        self::$routeMaps[$path]['action'],
                        self::$routeMaps[$path]['dynamic']
                );
            }

        }

        //Above, we've already checked for matching static maps, so if this point
        // is reached, the request map is not a matched static map - although
        // it could be an unmatched static map
        foreach( self::$routeMaps as $route_map_path => $route_map )
        {
            //Any static maps here are ignored because any match would have
            // been caught earlier, so empty dynamic arrays are static and must
            // be ignored.
            $dynamic = $route_map['dynamic'];
            if( 0 === count($dynamic) )
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
            $dynamic_match = array();
            foreach( $dynamic_route_elements as $k => $dynamic_route_element )
            {
                 $regexp = $dynamic[$dynamic_route_element];

                 //NULL regexp specfied in the map match anything
                 if( NULL === $regexp )
                 {
                    $dynamic_match[$route_map_path_parts[$k]] = $path_parts[$k];
                    continue;
                 }

                 //Any regexp which does not match, results in a total failure
                 // of the current iteration
                 if( preg_match($regexp, $path_parts[$k]) > 0 )
                 {
                    $dynamic_match[$route_map_path_parts[$k]] = $path_parts[$k];
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
                if( 'dynamic' === $k )
                {
                    foreach( $route_map_element as $kk => $v )
                    {
                        $action_array[$k][$kk] = $dynamic_match[$kk];
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
                if( FALSE === is_array($v) && TRUE === array_key_exists($v, $action_array['dynamic']) )
                {
                    $action_array[$k] = $action_array['dynamic'][$v];
                }
            }

            return self::dispatch(
                    $action_array['controller'],
                    $action_array['action'],
                    $action_array['dynamic']
            );
        }
        else
        {
            self::$errors[] = 'Failed to find matching route';
            //If a custom url error handler is specfied, use it
            self::callUriErrorHandler(404, $path);
            return FALSE;
        }
    }

    /**
     * Dispatches controller, action, dynamic
     * @param string $controller
     * @param string $action
     * @param array $dynamic
     * @return boolean
     * @access private
     * @static
     */
    private static function dispatch( $controller, $action, $dynamic )
    {
        //When a custom dispatcher is specified, use it. The remaining code
        // in this method is ignored.
        if( count(self::$customDispatcher) > 0 )
        {
            return call_user_func_array(
                array( self::$customDispatcher['class'], 
                       self::$customDispatcher['method']), 
                array($controller, $action, $dynamic)
            );
        }

        //Because the controller could have been matched as a dynamic element,
        // it would mean that the value in $controller is untrusted. Therefore,
        // it may only contain alphanumeric characters. Anything not matching
        // the regexp is considered potentially harmful.
        preg_match("/^[a-zA-Z0-9_-]+$/", $controller, $matches);
        if( count($matches) !== 1 )
        {
            self::$errors[] = "An invalid character was found in the controller name {$controller}";
            self::callUriErrorHandler(400, self::$requestedPath);
            return FALSE;
        }

        //Determine the file name
        $file = $controller . self::$controllerSuffix;
        $file_path = self::$controllerPath . '/';
        $file_name = $file_path . $file;

        //The controller class should be in a file named something like;
        // '{$controller}.class.php' or '{$controller}Controller.php'.
        //In order to determine what the controller name should be,
        // append any part of the suffix before the first '.' to the controller
        // name.
        $suffix_parts = explode('.', self::$controllerSuffix);
        $controller .= $suffix_parts[0];
        
        //If the file name does not end in '.php', it is a potential security
        // problem. Although this is set by the developer and not a user, it
        // is still considered potentially harmful if the developer does not
        // use the .php extension in the suffix.
        if( 'php' !== $suffix_parts[count($suffix_parts)-1] )
        {
            self::$errors[] = "The derived controller file name {$file} does not end in '.php'";
            self::callUriErrorHandler(500, self::$requestedPath);
            return FALSE;
        }

        //At this point, we are relatively assured that the file name is safe
        // to check for it's existence and require in.
        if( FALSE === file_exists($file_name) )
        {
            self::$errors[] = "Tried to dispatch but could not find the controller file {$file_name}";
            self::callUriErrorHandler(500, self::$requestedPath);
            return FALSE;
        }
        else
        {
            require_once($file_name);
        }

        //Check for the controller class
        if( FALSE === class_exists($controller) )
        {
            self::$errors[] = "The class {$controller} could not be found";
            self::callUriErrorHandler(500, $url);
            return FALSE;
        }

        //Check for the method
        if( FALSE === method_exists($controller, $action))
        {
            self::$errors[] = "The method {$action} could not be found";
            self::callUriErrorHandler(500, self::$requestedPath);
            return FALSE;
        }

        //All above checks should have confirmed that the controller and action
        // can be called
        return call_user_func(array($controller, $action), $dynamic);
    }

    /**
     * Sets a custom dispatcher
     * @param mixed $class the class name or object
     * @param string $method the name of dispatch method
     * @static
     * @access public
     * @return void
     */
    public static function setDispatcher( $class, $method )
    {
        self::$customDispatcher = array( 'class' => $class, 'method' => $method);
    }

    /**
     * Sets the page not found handler. The custom handler should accept the path
     * as an argument.
     * @param mixed $class the class name of the handler or an instance
     * @param string $method the method in the class or object to invoke
     * @access public
     * @static
     * @return void
     */
    public static function setErrorHandler( $class, $method )
    {
        self::$customUriErrorHandler = array( 'class' => $class, 'method' => $method);
    }

    /**
     * If set, calls the custom uri error handler and passes $error_code & $path
     * @param int $error_code - the http error code (i.e. 404,
     * @param string $path
     * @access private
     * @return void
     * @static
     */
    private static function callUriErrorHandler( $error_code, $path )
    {
        if( count(self::$customUriErrorHandler) > 0 )
        {
            call_user_func_array(array(
                self::$customUriErrorHandler['class'],
                self::$customUriErrorHandler['method']
            ), array($error_code, $path));
        }
    }

    /**
     * Sets the path to the controller classes
     * @param string $path
     * @param string $suffix The suffix to append to the controller file name
     * when including. The default is 'Controller.php'. The substring before the
     * first '.' gets appended to the controller name.
     * @access public
     * @return void
     */
    public static function setControllersPath( $path, $suffix = NULL )
    {
        self::$controllerPath = $path;
        if( NULL !== $suffix )
            self::$controllerSuffix = $suffix;
    }

    /**
     * Gets the errors array. Use for debugging only!
     * @return array
     * @access public
     * @static
     */
    public static function getErrors()
    {
        return self::$errors;
    }
}
?>
