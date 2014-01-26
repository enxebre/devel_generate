<?php
/**
 * @file
 *  Generate content, taxonomy, menu, and users via drush framework.
 */

/**
 * Implementation of hook_drush_command().
 */
function devel_generate_drush_command() {
  $items = array();
  $definitions = \Drupal::service('plugin.manager.develgenerate')->getDefinitions();

  foreach ($definitions as $id => $plugin_info) {
    $command_suffix = $plugin_info['drushSettings']['suffix'];
    $alias = $plugin_info['drushSettings']['alias'];
    $items["generate-$command_suffix"] = array(
      'callback' => 'drush_devel_generate',
      'description' => "Create $id.",
      'callback arguments' => array(
        'plugin_id' => $id,
      ),
      'aliases' => array("gen$alias"),
    );
    $items["generate-$command_suffix"]['options'] = $plugin_info['drushSettings']['options'];
    $items["generate-$command_suffix"]['arguments'] = $plugin_info['drushSettings']['args'];
  }

  return $items;
}

/**
 * Command callback.
 */
function drush_devel_generate() {
  //Array of "Callback arguments" and "command line args".
  $params = func_get_args();

  //Getting plugin_id and leaving the command line args
  $plugin_id = array_shift($params);

  $devel_generate_manager = \Drupal::service('plugin.manager.develgenerate');
  $instance = $devel_generate_manager->createInstance($plugin_id, array());

  //Plugin instance suit params in order to fit for generateElements
  $values = $instance->handleDrushParams($params);
  $instance->generate($values);
}
