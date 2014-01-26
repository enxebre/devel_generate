<?php
/**
 * Definition of \Drupal\devel_generate\Routing\DevelGenerateRouteSubscriber.
 */
namespace Drupal\devel_generate\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route event and add devel_generate route.
 */
class DevelGenerateRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection, $provider) {

    if ($provider != 'dynamic_routes') {
      return;
    }

    $devel_generate_plugins = $devel_generate_manager = \Drupal::service('plugin.manager.develgenerate')->getDefinitions();

    foreach ($devel_generate_plugins as $id => $plugin) {
      $type_url_str = str_replace('_', '-', $plugin['url']);
      $route = new Route(
        "admin/config/development/generate/$type_url_str",
        array(
          '_form' => '\Drupal\devel_generate\Form\DevelGenerateForm',
          '_title' => 'Generate',
          '_plugin_id' => $id,
        ),
        array(
          '_permission' => $plugin['permission'],
        )
      );
      $collection->add("devel_generate.$id", $route);
    }
  }
}
