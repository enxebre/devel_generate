<?php

/**
 * @file
 * Contains \Drupal\devel_generate\Form\GenerateForm.
 */

namespace Drupal\devel_generate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\devel_generate\DevelGenerateException;
/**
 * Defines a form that allows privileged users to generate entities.
 */
class DevelGenerateForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'devel_generate_form_' . $this->getPluginIdFromRequest();
  }

  protected function getPluginIdFromRequest() {
    $request = $this->getRequest();
    return $request->get('_plugin_id');
  }

  public function getPluginInstance() {
    $devel_generate_manager = $this->container()->get('plugin.manager.develgenerate');
    $element_to_generate = $this->getPluginIdFromRequest();
    $instance = $devel_generate_manager->createInstance($element_to_generate);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $instance = $this->getPluginInstance();
    $form = $instance->settingsForm($form, $form_state);
    $form_state['instance'] = $instance;
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

    try {
      $values = $form_state['values'];
      $instance = $form_state['instance'];
      $instance->generate($values);
    }
    catch (DevelGenerateException $e) {
      watchdog('DevelGenerate', 'Failed to generate elements due to "%error".', array('%error' => $e->getMessage()), WATCHDOG_WARNING);
      drupal_set_message($this->t('Failed to generate elements due to "%error".', array('%error' => $e->getMessage())));
    }
  }

}
