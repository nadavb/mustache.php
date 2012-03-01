<?php

namespace Mustache\Test\Functional;

use Mustache\Mustache;
use Mustache\Loader\StringLoader;

/**
 * A PHPUnit test case wrapping the Mustache Spec
 *
 * @group mustache-spec
 * @group functional
 */
class MustacheSpecTest extends \PHPUnit_Framework_TestCase {

	private static $mustache;

	public static function setUpBeforeClass() {
		self::$mustache = new Mustache;
	}

	/**
	 * For some reason data providers can't mark tests skipped, so this test exists
	 * simply to provide a 'skipped' test if the `spec` submodule isn't initialized.
	 */
	public function testSpecInitialized() {
		if (!file_exists(__DIR__.'/../../../spec/specs/')) {
			$this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
		}
	}

	/**
	 * @group comments
	 * @dataProvider loadCommentSpec
	 */
	public function testCommentSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadCommentSpec() {
		return $this->loadSpec('comments');
	}

	/**
	 * @group delimiters
	 * @dataProvider loadDelimitersSpec
	 */
	public function testDelimitersSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadDelimitersSpec() {
		return $this->loadSpec('delimiters');
	}

	/**
	 * @group interpolation
	 * @dataProvider loadInterpolationSpec
	 */
	public function testInterpolationSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadInterpolationSpec() {
		return $this->loadSpec('interpolation');
	}

	/**
	 * @group inverted
	 * @group inverted-sections
	 * @dataProvider loadInvertedSpec
	 */
	public function testInvertedSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadInvertedSpec() {
		return $this->loadSpec('inverted');
	}

	/**
	 * @group lambdas
	 * @dataProvider loadLambdasSpec
	 */
	public function testLambdasSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($this->prepareLambdasSpec($data)), $desc);
	}

	public function loadLambdasSpec() {
		return $this->loadSpec('~lambdas');
	}

	/**
	 * Extract and lambdafy any 'lambda' values found in the $data array.
	 */
	private function prepareLambdasSpec($data) {
		foreach ($data as $key => $val) {
			if ($key === 'lambda') {
				if (!isset($val['php'])) {
					$this->markTestSkipped(sprintf('PHP lambda test not implemented for this test.'));
				}

				$func = $val['php'];
				$data[$key] = function($text = null) use ($func) { return eval($func); };
			} else if (is_array($val)) {
				$data[$key] = $this->prepareLambdasSpec($val);
			}
		}
		return $data;
	}

	/**
	 * @group partials
	 * @dataProvider loadPartialsSpec
	 */
	public function testPartialsSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadPartialsSpec() {
		return $this->loadSpec('partials');
	}

	/**
	 * @group sections
	 * @dataProvider loadSectionsSpec
	 */
	public function testSectionsSpec($desc, $source, $partials, $data, $expected) {
		$template = self::loadTemplate($source, $partials);
		$this->assertEquals($expected, $template($data), $desc);
	}

	public function loadSectionsSpec() {
		return $this->loadSpec('sections');
	}

	/**
	 * Data provider for the mustache spec test.
	 *
	 * Loads YAML files from the spec and converts them to PHPisms.
	 *
	 * @access public
	 * @return array
	 */
	private function loadSpec($name) {
		$filename = __DIR__ . '/../../../spec/specs/' . $name . '.yml';
		if (!file_exists($filename)) {
			return array();
		}

		$data = array();
		$yaml = new \sfYamlParser;
		$file = file_get_contents($filename);

		// @hack: pre-process the 'lambdas' spec so the Symfony YAML parser doesn't complain.
		if ($name === '~lambdas') {
			$file = str_replace(" !code\n", "\n", $file);
		}

		$spec = $yaml->parse($file);

		foreach ($spec['tests'] as $test) {
			$data[] = array(
				$test['name'] . ': ' . $test['desc'],
				$test['template'],
				isset($test['partials']) ? $test['partials'] : array(),
				$test['data'],
				$test['expected'],
			);
		}

		return $data;
	}

	private static function loadTemplate($source, $partials) {
		self::$mustache->setPartials($partials);

		return self::$mustache->loadTemplate($source);
	}
}
