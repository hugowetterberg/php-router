<?php

require_once('PHPUnit/Framework.php');
require_once 'Mockery/Framework.php';
include_once(dirname(__FILE__) . '/../lib/Router.php');

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testAddRoute()
    {
        $router = new Router;

        $route = mockery('Route');

        $router->addRoute( 'myroute', $route );

        $routes = $router->getRoutes();
        
        $this->assertTrue( array_key_exists('myroute', $routes));
    }

    public function testGetLink()
    {
        $router = new Router;

        $route = mockery('Route', array(
            'getDynamicElements' => array(
                ':class' => ':class',
                ':method' => ':method',
                ':id' => ':id'
            ),
            'getPath' => '/:class/:method/:id'
        ));

        $router->addRoute( 'myroute', $route );

        $url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'        => '1'
        ));
        
        $this->assertSame('/myclass/mymethod/1', $url);
    }

    public function testFailGetLink()
    {
        $router = new Router;

        $route = mockery('Route', array(
            'getDynamicElements' => array(
                ':class' => ':class',
                ':method' => ':method',
                ':id' => ':id'
            ),
            'getPath' => '/:class/:method/:id'
        ));

        $router->addRoute( 'myroute', $route );
        
        //should create '/myclass/mymethod/1'
        $url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'        => '1'
        ));
        
        $this->assertNotSame('/myclass/mymethod/2', $url);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testGetUrlNonExistentRoute()
    {
        $router = new Router;

        $route = mockery('Route', array(
            'getDynamicElements' => array(
                ':class' => ':class',
                ':method' => ':method',
                ':id' => ':id'
            ),
            'getPath' => '/:class/:method/:id'
        ));

        $router->addRoute( 'myroute', $route );

        //a php error should be triggered
        $failed_url = $router->getUrl( 'not_there', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'        => 1
        ));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testGetUrlWrongArgumentForNamedRoute()
    {
        $router = new Router;
        
        $route = mockery('Route', array(
            'getDynamicElements' => array(
                ':class' => ':class',
                ':method' => ':method',
                ':id' => ':id'
            ),
            'getPath' => '/:class/:method/:id'
        ));

        $router->addRoute( 'myroute', $route );

        //a php error should be triggered
        $failed_url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':wrong'        => 1
        ));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testGetUrlWrongNumberOfArgumentsForNamedRoutes()
    {
        $router = new Router;

        $route = mockery('Route', array(
            'getDynamicElements' => array(
                ':class' => ':class',
                ':method' => ':method',
                ':id' => ':id'
            ),
            'getPath' => '/:class/:method/:id'
        ));

        $router->addRoute( 'myroute', $route );

        //a php error should be triggered
        $failed_url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod'
        ));
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRoute()
    {
        $router = new Router;

        $path = '/find/this/class';
        
        $route = mockery('Route', array(
            'matchMap' => TRUE,
            'getPath' => $path
        ));

        

        $router->addRoute( 'myroute', $route );

        $found_route = $router->findRoute( $path );

        $this->assertSame($route, $found_route);
        
    }

    /**
     * @depends testAddRoute
     */
    public function testFailToFindRoute()
    {
        $router = new Router;

        $route = mockery('Route', array(
            'matchMap' => FALSE,
            'getPath' => '/find/this/route'
        ));

        $router->addRoute( 'myroute', $route );

        $found_route = $router->findRoute( '/fail/to/find/this/route' );

        $this->assertFalse( $found_route );
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRouteInManyRoutes()
    {
        $router = new Router;

        $id_route = mockery('Route', array(
            'matchMap' => TRUE
        ));

        $router->addRoute( 'id', $id_route );

        //Here is a default route (should go last)
        $def_route = mockery('Route', array(
            'matchMap' => FALSE
        ));
    
        $router->addRoute( 'default', $def_route );

        //We should only find the id_route defined above
        $find_path = '/parts/show/100';
        $found_route = $router->findRoute( $find_path );
        
        if( TRUE === is_object( $found_route ) )
        {
            $this->assertSame($id_route, $found_route);
        }
        else
        {
            $this->fail( 'Found result is not an Object' );
        }
    }
}

?>
