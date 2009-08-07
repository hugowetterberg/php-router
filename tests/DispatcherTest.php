<?php
require_once('PHPUnit/Framework.php');
require_once('../src/Dispatcher.php');
require_once('../src/Route.php');

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    private $route;

    public function setUp()
    {
        $this->helperCreateRouteObject();
    }

    public function tearDown()
    {
        @unlink('fooClass.php');
        @unlink('noclassnameClass.php');
    }

    public function helperCreateRouteObject()
    {
        $this->route = new Route;

        $this->route->setPath( '/:class/:method/:id' );

        $this->route->addDynamicElement( ':class', ':class' );
        $this->route->addDynamicElement( ':method', ':method' );
        $this->route->addDynamicElement( ':id', ':id' );

        Dispatcher::setSuffix('Class');
    }

    public function helperCreateTestClassFile()
    {
        $contents = "<?php\n"
                  . "class fooClass {\n"
                  . "    public function bar( \$args ) {\n"
                  . "        //print_r(\$args);\n"
                  . "    }\n"
                  . "}\n"
                  . "?>\n"
                  ;

        $fh = fopen('fooClass.php', 'w');
        fwrite($fh, $contents);
        fclose($fh);
    }

    public function testCatchClassFileNotFound()
    {
        $this->helperCreateRouteObject();

        $this->route->matchMap('/no_class/bar/55');
        
        try {
            Dispatcher::dispatch( $this->route );
        } catch ( classFileNotFoundException $exception ) {
            return;
        }
            
        $this->fail('Try Catch failed ');
    }

    public function testCatchClassNameNotFound()
    {
        $contents = "<?php\n"
                  . "class noclassnamefoundClass {\n"
                  . "    public function bar( \$args ) {\n"
                  . "        //print_r(\$args);\n"
                  . "    }\n"
                  . "}\n"
                  . "?>\n"
                  ;

        $fh = fopen('noclassnameClass.php', 'w');
        fwrite($fh, $contents);
        fclose($fh);

        $this->route->matchMap('/noclassname/bar/55');

        try {
            Dispatcher::dispatch( $this->route );
        } catch ( classNameNotFoundException $exception ) {
            return;
        }


        $this->fail('Catching class name not found failed ');
    }

    public function testCatchClassNotSpecified()
    {
        $this->route->matchMap('/ /method/55');

        try {
            Dispatcher::dispatch( $this->route );
        } catch ( classNotSpecifiedException $exception ) {
            return;
        }


        $this->fail('Catching class not specified failed ');
    }

    public function testCatchBadClassName()
    {
        $this->helperCreateRouteObject();

        $this->route->matchMap('/foo\"/bar/55');

        try {
            Dispatcher::dispatch( $this->route );
        } catch ( badClassNameException $exception ) {
            return;
        }


        $this->fail('Catching bad class name failed ');
    }

    public function testCatchMethodNotSpecified()
    {
        $this->helperCreateRouteObject();

        $this->helperCreateTestClassFile();

        $this->route->matchMap('/foo/ /55');

        try {
            Dispatcher::dispatch( $this->route );
        } catch ( methodNotSpecifiedException $exception ) {
            return;
        }


        $this->fail('Catching method not specified failed ');
    }

    public function testCatchClassMethodNotFound()
    {
        $this->helperCreateRouteObject();

        $this->helperCreateTestClassFile();

        $this->route->matchMap('/foo/nomethod/55');

        try {
            Dispatcher::dispatch( $this->route );
        } catch ( classMethodNotFoundException $exception ) {
            return;
        }


        $this->fail('Catching class method not found failed ');
    }

    public function testSuccessfulDispatch()
    {
        $this->helperCreateRouteObject();
        
        $this->helperCreateTestClassFile();

        if( TRUE === $this->route->matchMap('/foo/bar/55') )
        {
            $res = Dispatcher::dispatch($this->route);
            $this->isTrue( $res );
        }
        else
        {
            $this->fail('The route could not be mapped');
        }

        
    }

    public function testFailDispatch()
    {
        $this->helperCreateRouteObject();

        $this->helperCreateTestClassFile();

        if( FALSE === $this->route->matchMap('/im/not/in/here') )
        {
            try{
                Dispatcher::dispatch($this->route);
            } catch (classNotSpecifiedException $exception) {
                return;
            }
        }

        $this->fail('Should have caught classNotSpecifiedException, but did not');
    }
 
}

?>
