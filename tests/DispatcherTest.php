<?php
require_once('PHPUnit/Framework.php');
require_once 'Mockery/Framework.php';
include_once(dirname(__FILE__) . '/../lib/Dispatcher.php');

class DispatcherTest extends PHPUnit_Framework_TestCase
{

   

    public function tearDown()
    {
        @unlink('fooClass.php');
        @unlink('noclassnameClass.php');
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
        $route = mockery('Route', array(
            'matchMap'      => TRUE,
            'getMapClass'   => 'class',
            'getMapMethod'  => 'method'
        ));

        $route->matchMap('/no_class/bar/55');

        $dispatcher = new Dispatcher;
        
        try {
            $dispatcher->dispatch( $route );
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

        $route = mockery('Route', array(
            'matchMap'      => TRUE,
            'getMapClass'   => 'noclassnameClass',
            'getMapMethod'  => 'method'
        ));

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( classNameNotFoundException $exception ) {
            return;
        }


        $this->fail('Catching class name not found failed ');
    }

    public function testCatchClassNotSpecified()
    {
        $route = mockery('Route', array(
            'matchMap'      => FALSE,
            'getMapClass'   => '',
            'getMapMethod'  => 'method'
        ));

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( classNotSpecifiedException $exception ) {
            return;
        }

        $this->fail('Catching class not specified failed ');
    }

    public function testCatchBadClassName()
    {
        $route = mockery('Route', array(
            'matchMap'      => FALSE,
            'getMapClass'   => 'foo\"',
            'getMapMethod'  => 'method'
        ));

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( badClassNameException $exception ) {
            return;
        }

        $this->fail('Catching bad class name failed ');
    }

    public function testCatchMethodNotSpecified()
    {
        $this->helperCreateTestClassFile();

        $route = mockery('Route', array(
            'matchMap'      => FALSE,
            'getMapClass'   => 'foo',
            'getMapMethod'  => ''
        ));

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( methodNotSpecifiedException $exception ) {
            return;
        }


        $this->fail('Catching method not specified failed ');
    }

    public function testCatchClassMethodNotFound()
    {
        $this->helperCreateTestClassFile();

        $route = mockery('Route', array(
            'matchMap'      => TRUE,
            'getMapClass'   => 'foo',
            'getMapMethod'  => 'nomethod'
        ));

        $dispatcher = new Dispatcher;
        $dispatcher->setSuffix('Class');

        try {
           $dispatcher->dispatch( $route );
        } catch ( classMethodNotFoundException $exception ) {
            return;
        }

        $this->fail('Catching class method not found failed ');
    }

    public function testSuccessfulDispatch()
    {
        $this->helperCreateTestClassFile();

        $route = mockery('Route', array(
            'matchMap'      => TRUE,
            'getMapClass'   => 'foo',
            'getMapMethod'  => 'bar'
        ));

        $dispatcher = new Dispatcher;
        $dispatcher->setSuffix('Class');

        if( TRUE === $route->matchMap('/foo/bar/55') )
        {
            $res = $dispatcher->dispatch($route);
            $this->isTrue( $res );
        }
        else
        {
            $this->fail('The route could not be mapped');
        }

        
    }
 
}

?>
