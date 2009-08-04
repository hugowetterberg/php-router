<?php

require_once('PHPUnit/Framework.php');
require_once('../src/Route.php');
require_once('../src/Router.php');

class RouterTest extends PHPUnit_Framework_TestCase
{
    

    public function testAddRoute()
    {
        Router::resetRouter();

        $route = new Route;

        $route->setPath( '/:class/:method/:id' );

        $route->addDynamicElement( ':class', ':class' );
        $route->addDynamicElement( ':method', ':method' );
        $route->addDynamicElement( ':id', ':id' );

        Router::addRoute( $route );

        $routes = Router::getRoutes();
        
        $this->assertTrue( in_array($route, $routes));
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRoute()
    {
        Router::resetRouter();

        $route = new Route;

        $path = '/find/this/class';
        $route->setPath( $path );

        Router::addRoute( $route );

        $found_route = Router::findRoute( $path );

        $this->assertSame($route, $found_route);
        
    }

    /**
     * @depends testAddRoute
     */
    public function testFailToFindRoute()
    {
        Router::resetRouter();

        $route = new Route;

        $path = '/find/this/route';
        $route->setPath( $path );

        Router::addRoute( $route );

        $found_route = Router::findRoute( '/fail/to/find/this/route' );

        $this->assertFalse( $found_route );
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRouteInManyRoutes()
    {
        Router::resetRouter();

        $id_route = new Route;
        $id_route->setPath( '/:class/:method/:id' );
        $id_route->addDynamicElement( ':class', '^parts' );
        $id_route->addDynamicElement( ':method', ':method' );
        $id_route->addDynamicElement( ':id', '^\d{3}$' );
        Router::addRoute( $id_route );

        //Here is a default route (should go last)
        $def_route = new Route;
        $def_route->setPath( '/:class/:method/:id' );
        $def_route->addDynamicElement( ':class', ':class' );
        $def_route->addDynamicElement( ':method', ':method' );
        $def_route->addDynamicElement( ':id', ':id' );
        Router::addRoute( $def_route );

        //We should only find the id_route defined above
        $find_path = '/parts/show/100';
        $found_route = Router::findRoute( $find_path );
        
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
