<?php

namespace Drupal\devel_generate;

class DevelGenerateFieldFactory {

  public function createInstance($fieldType) {
    $fieldType = ucfirst($fieldType);
    $class = "Drupal\devel_generate\DevelGenerateField$fieldType";

    if (!class_exists($class)) {
      throw new DevelGenerateException(sprintf('The field type (%s) did not specify an instance class.', $fieldType));
    }

    return new $class();
  }

}
