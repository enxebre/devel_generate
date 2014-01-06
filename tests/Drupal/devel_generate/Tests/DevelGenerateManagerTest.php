<?php

namespace Drupal\devel_generate\Tests;

use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DevelGenerateManager.
 *
 */
class DevelGenerateManagerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'DevelGenerate manager',
      'description' => 'DevelGenerate manager',
      'group' => 'DevelGenerate manager',
    );
  }

  public function testCreateInstance() {

    $namespaces = new \ArrayObject(array('Drupal\devel_generate_example' => realpath(dirname(__FILE__) . '/../../../modules/devel_generate_example/lib')));

    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManager');

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $manager = new DevelGeneratePluginManager($namespaces, $cache_backend, $language_manager, $module_handler);

    $example_instance = $manager->createInstance('example');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf('Drupal\devel_generate_example\Plugin\DevelGenerate\ExampleDevelGenerate', $example_instance);
    $this->assertArrayHasKey('url', $plugin_def);
    $this->assertTrue($plugin_def['url'] == 'example');
  }
}
