<?php
/**
 * @author Rob Apodaca <rob.apodaca@gmail.com>
 * @copyright Copyright (c) 2009, Rob Apodaca
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://robap.github.com/php-router/
 */
class Route
{
    /**
     * The Route path consisting of route elements
     * @var string
     */
    private $_path;

    /**
     * The name of the class that this route maps to
     * @var string
     */
    private $_class;

    /**
     * The name of the class method that this route maps to
     * @var string
     */
    private $_method;
    
    /**
     * Stores any set dynamic elements
     * @var array 
     */
    private $_dynamicElements = array();
    
    /**
     * Stores any arguments found when mapping
     * @var array 
     */
    private $_mapArguments = array();

    /**
     * Class Constructor
     * @param string $path optional
     */
    public function __construct( $path = NULL )
    {
        if( NULL !== $path )
            $this->setPath( $path );
    }

    /**
     * Set the route path
     * @param string $path
     * @return void
     * @access public
     */
    public function setPath( $path )
    {
        $this->_path = $path;
    }

    /**
     * Get the route path
     * @return string
     * @access public
     */
    public function getPath()
    {
        return $this->_path;
    }
    /**
     * Set the map class name
     * @param string $class
     * @access public
     * @return void
     */
    public function setMapClass( $class )
    {
        $this->_class = $class;
    }

    /**
     * Get the map class name
     * @return string
     * @access public
     */
    public function getMapClass()
    {
        return $this->_class;
    }
    
    /**
     * Sets the map method name
     * @param string $method
     * @access public
     * @return void
     */
    public function setMapMethod( $method )
    {
        $this->_method = $method;
    }

    /**
     * Gets the currently set map method
     * @return string
     * @access public
     */
    public function getMapMethod()
    {
        return $this->_method;
    }

    /**
     * Adds a dynamic element to the Route
     * @param string $key
     * @param string $value
     * @access public
     * @return void
     */
    public function addDynamicElement( $key, $value )
    {
        $this->_dynamicElements[$key] = $value;
    }

    /**
     * Get the dynamic elements array
     * @return array
     * @access public
     */
    public function getDynamicElements()
    {
        return $this->_dynamicElements;
    }

    /**
     * Adds a found argument to the _mapArguments array
     * @param string $key
     * @param string $value
     * @access public
     * @return void
     */
    private function _addMapArguments( $key, $value )
    {
        $this->_mapArguments[$key] = $value;
    }
    
    /**
     * Gets the _mapArguments array
     * @return array
     * @access public
     */
    public function getMapArguments()
    {
        return $this->_mapArguments;
    }

    /**
     * Attempt to match this route to a supplied path
     * @param string $path_to_match
     * @return boolean
     * @access public
     */
    public function matchMap( $path_to_match )
    {
        $found_dynamic_class  = NULL;
        $found_dynamic_method = NULL;
        $found_dynamic_args   = array();

        //The process of matching is easier if there are no preceding slashes
        $temp_this_path     = ereg_replace('^/', '', $this->_path);
        $temp_path_to_match = ereg_replace('^/', '', $path_to_match);

        //Get the path elements used for matching later
        $this_path_elements  = explode('/', $temp_this_path);
        $match_path_elements = explode('/', $temp_path_to_match);

        //If the number of elements in each path is not the same, there is no
        // way this could be it.
        if( count($this_path_elements) !== count($match_path_elements) )
            return FALSE;

        //Construct a path string that will be used for matching
        $possible_match_string = '';
        foreach( $this_path_elements as $i => $this_path_element )
        {
            // ':'s are never allowed at the beginning of the path element
            if( preg_match('/^:/', $match_path_elements[$i]) )
            {
                return FALSE;
            }

            //This element may simply be static, if so the direct comparison
            // will discover it.
            if( $this_path_element === $match_path_elements[$i] )
            {
                $possible_match_string .= "/{$match_path_elements[$i]}";
                continue;
            }

            //Consult the dynamic array for help in matching
            if( TRUE === isset($this->_dynamicElements[$this_path_element]) )
            {
                //The dynamic array either contains a key like ':id' or a
                // regular expression. In the case of a key, the key matches
                // anything
                if( $this->_dynamicElements[$this_path_element] === $this_path_element )
                {
                    $possible_match_string .= "/{$match_path_elements[$i]}";

                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if( ':class' === $this_path_element && NULL === $this->getMapClass() )
                    {
                        $found_dynamic_class = $match_path_elements[$i];
                    }
                    else if( ':method' === $this_path_element && NULL === $this->getMapMethod() )
                    {
                        $found_dynamic_method = $match_path_elements[$i];
                    }
                    else if( ':class' !== $this_path_element && ':method' !== $this_path_element )
                    {
                        $found_dynamic_args[$this_path_element] = $match_path_elements[$i];
                    }

                    continue;
                }

                //Attempt a regular expression match
                $regexp = '/' . $this->_dynamicElements[$this_path_element] . '/';
                if( preg_match( $regexp, $match_path_elements[$i] ) > 0 )
                {
                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if( ':class' === $this_path_element && NULL === $this->getMapClass() )
                    {
                        $found_dynamic_class = $match_path_elements[$i];
                    }
                    else if( ':method' === $this_path_element && NULL === $this->getMapMethod() )
                    {
                        $found_dynamic_method = $match_path_elements[$i];
                    }
                    else if( ':class' !== $this_path_element && ':method' !== $this_path_element )
                    {
                        $found_dynamic_args[$this_path_element] = $match_path_elements[$i];
                    }

                    $possible_match_string .= "/{$match_path_elements[$i]}";
                    continue;
                }
            }

            // In order for a full match to succeed, all iterations must match.
            // Because we are continuing with the next loop if any conditions
            // above are met, if this point is reached, this route cannot be
            // a match.
            return FALSE;
        }
        
        //Do the final comparison and return the result
        if( $possible_match_string === $path_to_match )
        {
            if( NULL !== $found_dynamic_class )
                $this->setMapClass($found_dynamic_class);

            if( NULL !== $found_dynamic_method )
                $this->setMapMethod($found_dynamic_method);

            foreach( $found_dynamic_args as $key => $found_dynamic_arg )
            {
                $this->_addMapArguments($key, $found_dynamic_arg);
            }

            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}

?>
