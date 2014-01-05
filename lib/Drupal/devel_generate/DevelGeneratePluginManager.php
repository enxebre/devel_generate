<?php
/**
 * @file
 * Contains \Drupal\devel_generate\DevelGeneratePluginManager.
 */

namespace Drupal\devel_generate;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Plugin type manager for DevelGenerate plugins.
 */
Class DevelGeneratePluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {

    parent::__construct('Plugin/DevelGenerate', $namespaces, 'Drupal\devel_generate\Annotation\DevelGenerate');

    $this->setCacheBackend($cache_backend, $language_manager, 'devel_generate_plugins');
    $this->alterInfo($module_handler, 'devel_generate_info');

  }
}
