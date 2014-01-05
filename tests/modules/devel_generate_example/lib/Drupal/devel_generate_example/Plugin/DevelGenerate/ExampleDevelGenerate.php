<?php

/**
 * @file
 * Contains \Drupal\devel_generate\Plugin\DevelGenerate\UserDevelGenerate.
 */

namespace Drupal\devel_generate_example\Plugin\DevelGenerate;

use Drupal\devel_generate\DevelGenerateBase;

/**
 * Provides a ExampleDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "example",
 *   label = @Translation("Example"),
 *   description = @Translation("Generate a given number of examples. Optionally delete current examples."),
 *   url = "example",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE
 *   },
 *   drushSettings = {
 *     "suffix" = "examples",
 *     "alias" = "exm",
 *     "options" = {
 *        "kill" = "Delete existing examples"
 *      },
 *     "args" = {
 *        "num" = "Number of examples to create"
 *     }
 *   }
 * )
 */
class ExampleDevelGenerate extends DevelGenerateBase {

  public function settingsForm(array $form, array &$form_state) {

    $form['num'] = array(
      '#type' => 'textfield',
      '#title' => t('How many examples would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#size' => 10,
    );

    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete all examples before generating new examples.'),
      '#default_value' => $this->getSetting('kill'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $num = $values['num'];
    $kill = $values['kill'];

    if ($kill) {
        $this->setMessage(t('Old examples have been deleted.'));
    }

    $this->setMessage(t('!num_examples created.', array('!num_examples' => \Drupal::translation()->formatPlural($num, '1 example', '@count examples'))));
  }

  public function handleDrushParams($args) {
    $values = array(
      'num' => array_shift($args),
      'kill' => drush_get_option('kill'),
    );
    return $values;
  }

}
