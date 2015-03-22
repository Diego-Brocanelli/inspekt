<?php

use Inspekt\Inspekt;
use Inspekt\Cage;

/**
 * Test class for Cage.
 */
class CageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Cage
     */
    protected $cage;

    /**
     * @var  string
     */
    protected $assetPath;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $inputarray['html'] = '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">';
        $inputarray['int'] = 7;
        $inputarray['input'] = '<img id="475">yes</img>';
        $inputarray['to_int'] = '109845 09471fjorowijf blab$';
        $inputarray['lowascii'] = '    ';
        $inputarray[] = array('foo', 'bar<br />', 'yes<P>', 1776);
        $inputarray['x']['woot'] = array(
            'booyah' => 'meet at the bar at 7:30 pm',
            'ultimate' => '<strong>hi there!</strong>',
        );
        $inputarray['lemon'][][][][][][][][][][][][][][] = 'far';

        $this->cage = Cage::Factory($inputarray);
        $this->assetPath = dirname(__FILE__) . '/assets';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $_GET = array();
        $_POST = array();
    }

    /**
     */
    public function testFactory()
    {
        $foo = array('blazm'=>'bar', 'blau'=>'baz');
        $cage = Cage::factory($foo);
        $this->assertSame('Inspekt\Cage', get_class($cage));
    }

    /**
     */
    public function testGetIterator()
    {
        $foo = array('blazm'=>'bar', 'blau'=>'baz');
        $cage = Cage::factory($foo);
        $iter = $cage->getIterator();
        $this->assertSame('ArrayIterator', get_class($iter));
    }

    /**
     */
    public function testOffsetSet()
    {
        $foo = array('blazm'=>'bar', 'blau'=>'baz');
        $cage = Cage::factory($foo);
        $cage->offsetSet('foo', 'bar');
        $expected = $cage->getRaw('foo');

        $this->assertSame('bar', $expected);
    }

    /**
     */
    public function testOffsetExists()
    {
        $foo = array('blazm'=>'bar', 'blau'=>'baz');
        $cage = Cage::factory($foo);
        $cage->offsetSet('foo', 'bar');
        $this->assertTrue($cage->offsetExists('blazm'));
        $this->assertTrue($cage->offsetExists('blau'));
        $this->assertTrue($cage->offsetExists('foo'));
        $this->assertFalse($cage->offsetExists('nope'));
    }

    /**
     */
    public function testOffsetUnset()
    {
        $foo = array('blazm'=>'bar', 'blau'=>'baz');
        $cage = Cage::factory($foo);
        $cage->offsetSet('foo', 'bar');
        $expected = $cage->getRaw('foo');

        $this->assertSame('bar', $expected);

        $cage->offsetUnset('foo');
        $this->assertFalse($cage->offsetExists('for'));
    }

    /**
     */
    public function testOffsetGet()
    {
        $foo = array('foo'=>'bar');
        $cage = Cage::factory($foo);
        $this->assertSame('bar', $cage->offsetGet('foo'));
    }

    /**
     */
    public function testCount()
    {
        $foo = array('foo'=>'bar', 'bar'=>'baz');
        $cage = Cage::factory($foo);
        $this->assertSame(2, $cage->count());
    }

    /**
     */
    public function testGetSetHTMLPurifier()
    {
        $hp = new \HTMLPurifier();
        $foo = array('foo'=>'bar', 'bar'=>'baz');
        $cage = Cage::factory($foo);
        $cage->setHTMLPurifier($hp);
        $this->assertTrue($cage->getHTMLPurifier() instanceof \HTMLPurifier);
    }


    /**
     */
    public function testParseAndApplyAutoFilters()
    {
        $foo = array(
            'userid'=>'--12<strong>34</strong>',
            'username'=>'se777v77enty_<em>fiv</em>e!',
        );
        $config_file = $this->assetPath . '/config_cage.ini';
        $cage = Cage::factory($foo, $config_file);

        $this->assertTrue(1234 === ($cage->getRaw('userid')));
        $this->assertTrue('seventyfive' === ($cage->getRaw('username')));
    }


    /**
     *
     */
    public function testAddAccessor()
    {
        //pre-condition, clean start
        $this->assertSame($this->cage->user_accessors, array());
        $this->cage->addAccessor('method_name');
        $this->assertSame($this->cage->user_accessors, array('method_name'));
    }

    /**
     */
    public function testGetAlpha()
    {
        /**
         * $inputarray['x']['woot'] = array(
         *     'booyah' => 'meet at the bar at 7:30 pm',
         */
        $this->assertSame('meetatthebaratpm', $this->cage->getAlpha('x/woot/booyah'));
    }

    /**
     */
    public function testGetAlnum()
    {
        /**
         * $inputarray['x']['woot'] = array(
         *     'booyah' => 'meet at the bar at 7:30 pm',
         */
        $this->assertSame('meetatthebarat730pm', $this->cage->getAlnum('x/woot/booyah'));
    }

    /**
     */
    public function testGetDigits()
    {
        /**
         * $inputarray['x']['woot'] = array(
         *     'booyah' => 'meet at the bar at 7:30 pm',
         */
        $this->assertSame('730', $this->cage->getDigits('x/woot/booyah'));
    }

    /**
     */
    public function testGetDir()
    {
        $input = array('fullpath' => '/usr/lib/php/Pear.php');
        $cage = Cage::factory($input);
        $this->assertSame(
            '/usr/lib/php',
            $cage->getDir('fullpath')
        );
    }

    /**
     *
     */
    public function testGetInt()
    {
        /**
         * 109845 09471fjorowijf blab$
         */
        $this->assertSame(109845, $this->cage->getInt('to_int'));
    }

    /**
     *
     */
    public function testGetInt2()
    {
        $this->assertSame($this->cage->getInt('int'), 7);
    }

    /**
     */
    public function testGetPath()
    {
        $old_cwd = getcwd();

        $path_array = array(
            'one' => './',
            'two' => './../../',
        );

        $cage = Cage::factory($path_array);

        chdir(dirname(__FILE__));

        $expected = dirname(__FILE__);
        $this->assertSame($cage->getPath('one'), $expected);

        $expected = dirname(dirname(dirname(__FILE__)));
        $this->assertSame($cage->getPath('two'), $expected);

        chdir($old_cwd);
    }

    /**
     */
    public function testGetROT13()
    {
        $input = $this->cage->getROT13('input');
        $this->assertSame('<vzt vq="475">lrf</vzt>', $this->cage->getROT13('input'));
    }

    /**
     */
    public function testGetPurifiedHTML()
    {
        $inputarray['html'] = array(
            'xss' => '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">',
            'bad_nesting' => '<p>This is a malformed fragment of <em>HTML</p></em>',
        );

        $cage = Cage::Factory($inputarray);
        $cage->loadHTMLPurifier();

        $this->assertSame("\"&gt;", $cage->getPurifiedHTML('html/xss'));
        $this->assertSame("<p>This is a malformed fragment of <em>HTML</em></p>",
            $cage->getPurifiedHTML('html/bad_nesting'));
    }

    /**
     * @expectedException     \Inspekt\Exception
     */
    public function testGetRaw()
    {
        $this->assertFalse($this->cage->getRaw('non-existant'));
    }

    /**
     *
     */
    public function testGetRaw2()
    {
        //test that found key returns matching value
        $this->assertEquals(
            $this->cage->getRaw('html'),
            '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">'
        );
    }

    /**
     *
     */
    public function testTestAlnum()
    {
        $_POST = array();
        $_POST['b'] = '0';
        $cage_POST = Inspekt::makePostCage();
        $result = $cage_POST->testAlnum('b');
        $this->assertSame('0', $result);
    }

    /**
     *
     */
    public function testTestAlnum2()
    {
        $_POST = array();
        $_POST['b'] = '2009-12-25';
        $cage_POST = Inspekt::makePostCage();
        $result = $cage_POST->testGreaterThan('b', 25);
        $this->assertSame(false, $result);
    }

    /**
     *
     */
    public function testTestAlnum3()
    {
        $_POST = array();
        $_POST['b'] = '0';
        $cage_POST = Inspekt::makePostCage();
        $result = $cage_POST->testLessThan('b', 25);
        $this->assertSame('0', $result);
    }

    /**
     */
    public function testTestAlpha()
    {
        $input = array(
            'values' => array(
                'input' => '0qhf01 *#R& !)*h09hqwe0fH! )efh0hf',
                'one' => '1241DOSLDH',
                'two' => 'efoihr123-',
                'three' => 'eoeijfol',
            ),
            'allgood' => array(
                'input' => 'asldifjlaskjg',
                'one' => 'wptopriowtg',
                'two' => 'WROIFWLVN',
                'three' => 'eoeijfol',
            )
        );
        $cage = Cage::factory($input);

        $this->assertFalse($cage->testAlpha('values/input'));
        $this->assertFalse($cage->testAlpha('values/one'));
        $this->assertFalse($cage->testAlpha('values/two'));
        $this->assertSame('eoeijfol', $cage->testAlpha('values/three'));

        var_dump($cage->testAlpha('allgood'));

        $this->assertFalse($cage->testAlpha('allgood'));
    }

    /**
     * @todo Implement testTestBetween().
     */
    public function testTestBetween()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestCcnum().
     */
    public function testTestCcnum()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestDate().
     */
    public function testTestDate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestDigits().
     */
    public function testTestDigits()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestEmail().
     */
    public function testTestEmail()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestFloat().
     */
    public function testTestFloat()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestGreaterThan().
     */
    public function testTestGreaterThan()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestHex().
     */
    public function testTestHex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestHostname().
     */
    public function testTestHostname()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestInt().
     */
    public function testTestInt()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestIp().
     */
    public function testTestIp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestLessThan().
     */
    public function testTestLessThan()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestOneOf().
     */
    public function testTestOneOf()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestPhone().
     */
    public function testTestPhone()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestRegex().
     */
    public function testTestRegex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestUri().
     */
    public function testTestUri()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testTestZip().
     */
    public function testTestZip()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testNoTags().
     */
    public function testNoTags()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testNoPath().
     */
    public function testNoPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testNoTagsOrSpecial().
     */
    public function testNoTagsOrSpecial()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testEscMySQL().
     */
    public function testEscMySQL()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testEscPgSQL().
     */
    public function testEscPgSQL()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testEscPgSQLBytea().
     */
    public function testEscPgSQLBytea()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement testKeyExists().
     */
    public function testKeyExists()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement test_keyExistsRecursive().
     */
    public function testKeyExistsRecursive()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement test_getValue().
     */
    public function testGetValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement test_getValueRecursive().
     */
    public function testGetValueRecursive()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement test_setValue().
     */
    public function testSetValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @todo Implement test_setValueRecursive().
     */
    public function testSetValueRecursive()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
