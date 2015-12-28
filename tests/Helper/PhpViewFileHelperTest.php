<?php
namespace Da\Tests\Helper;


use Da\Helper\PhpViewFileHelper;
use PHPUnit_Framework_TestCase;

class PhpViewFileHelperTest extends PHPUnit_Framework_TestCase
{
    public function testRender()
    {
        $view = __DIR__ . '/../data/test_view.php';
        $content = PhpViewFileHelper::render($view, ['force' => 'force', 'with' => 'with', 'you' => 'you']);

        $this->assertEquals("The force be with you!\n", $content);
    }
}
