<?php

/**
 * @file
 * Contains \Drupal\devel_generate\Plugin\DevelGenerate\ContentDevelGenerate.
 */

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\devel_generate\DevelGenerateBase;
use Drupal\Core\Language\Language;
use Drupal\devel_generate\DevelGenerateFieldBase;

/**
 * Provides a ContentDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "content",
 *   label = @Translation("content"),
 *   description = @Translation("Generate a given number of content. Optionally delete current content."),
 *   url = "content",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "max_comments" = 0,
 *     "title_length" = 4
 *   },
 *   drushSettings = {
 *     "suffix" = "content",
 *     "alias" = "c",
 *     "options" = {
 *        "kill" = "Delete all content before generating new content.",
 *        "types" = "A comma delimited list of content types to create. Defaults to page,article.",
 *        "feedback" = "An integer representing interval for insertion rate logging. Defaults to 1000",
 *        "skip-fields" = "A comma delimited list of fields to omit when generating random values",
 *        "languages" = "A comma-separated list of language codes"
 *      },
 *     "args" = {
 *        "number_nodes" = "Number of nodes to generate.",
 *        "maximum_comments" = "Maximum number of comments to generate."
 *     }
 *   }
 * )
 */
class ContentDevelGenerate extends DevelGenerateBase {

  public function settingsForm(array $form, array &$form_state) {

    $options = array();

    if (\Drupal::moduleHandler()->moduleExists('content')) {
      $types = content_types();
      foreach ($types as $type) {
        $warn = '';
        if (count($type['fields'])) {
          $warn = t('. This type contains CCK fields which will only be populated by fields that implement the content_generate hook.');
        }
        $options[$type['type']] = array('#markup' => t($type['name']). $warn);
      }
    }
    else {
      $types = node_type_get_types();
      foreach ($types as $type) {
        $options[$type->type] = array(
          'type' => array('#markup' => t($type->name)),
        );
        if (\Drupal::moduleHandler()->moduleExists('comment')) {
          $default = variable_get('comment_' . $type->type, COMMENT_OPEN);
          $map = array(t('Hidden'), t('Closed'), t('Open'));
          $options[$type->type]['comments'] = array('#markup' => '<small>'. $map[$default]. '</small>');
        }
      }
    }
    // we cannot currently generate valid polls.
    unset($options['poll']);

    if (empty($options)) {
      $this->setMessage(t('You do not have any content types that can be generated. <a href="@create-type">Go create a new content type</a> already!</a>', array('@create-type' => url('admin/structure/types/add'))), 'error', FALSE);
      return;
    }

    $header = array(
      'type' => t('Content type'),
    );
    if (\Drupal::moduleHandler()->moduleExists('comment')) {
      $header['comments'] = t('Comments');
    }

    $form['node_types'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#tableselect' => TRUE,
    );

    $form['node_types'] += $options;

    if (\Drupal::moduleHandler()->moduleExists('checkall')) $form['node_types']['#checkall'] = TRUE;
    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => t('<strong>Delete all content</strong> in these content types before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    );
    $form['num'] = array(
      '#type' => 'textfield',
      '#title' => t('How many nodes would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#size' => 10,
    );

    $options = array(1 => t('Now'));
    foreach (array(3600, 86400, 604800, 2592000, 31536000) as $interval) {
      $options[$interval] = \Drupal::service('date')->formatInterval($interval, 1) . ' ' . t('ago');
    }
    $form['time_range'] = array(
      '#type' => 'select',
      '#title' => t('How far back in time should the nodes be dated?'),
      '#description' => t('Node creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    );

    $form['max_comments'] = array(
      '#type' => \Drupal::moduleHandler()->moduleExists('comment') ? 'textfield' : 'value',
      '#title' => t('Maximum number of comments per node.'),
      '#description' => t('You must also enable comments for the content types you are generating. Note that some nodes will randomly receive zero comments. Some will receive the max.'),
      '#default_value' => $this->getSetting('max_comments'),
      '#size' => 3,
      '#access' => \Drupal::moduleHandler()->moduleExists('comment'),
    );
    $form['title_length'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum number of words in titles'),
      '#default_value' => $this->getSetting('title_length'),
      '#size' => 10,
    );
    $form['add_alias'] = array(
      '#type' => 'checkbox',
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('path'),
      '#description' => t('Requires path.module'),
      '#title' => t('Add an url alias for each node.'),
      '#default_value' => FALSE,
    );
    $form['add_statistics'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add statistics for each node (node_counter table).'),
      '#default_value' => TRUE,
      '#access' => \Drupal::moduleHandler()->moduleExists('statistics'),
    );

    unset($options);
    $options[Language::LANGCODE_NOT_SPECIFIED] = t('Language neutral');
    if (\Drupal::moduleHandler()->moduleExists('locale')) {
      $languages = language_list();
      foreach ($languages as $langcode => $language) {
        $options[$langcode] = $language->name;
      }
    }
    $form['add_language'] = array(
      '#type' => 'select',
      '#title' => t('Set language on nodes'),
      '#multiple' => TRUE,
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('locale'),
      '#description' => t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => array(Language::LANGCODE_NOT_SPECIFIED),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Generate'),
      '#tableselect' => TRUE,
    );
    $form['#redirect'] = FALSE;


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($values['num'] <= 50 && $values['max_comments'] <= 10) {
      if (!empty($values['kill'])) {
        $this->contentKill($values);
      }

      if (count($values['node_types'])) {
        // Generate nodes.
        $this->develGenerateContentPreNode($values);
        $start = time();
        for ($i = 1; $i <= $values['num']; $i++) {
          $this->develGenerateContentAddNode($values);
          if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
            $now = time();
            drush_log(dt('Completed !feedback nodes (!rate nodes/min)', array('!feedback' => drush_get_option('feedback', 1000), '!rate' => (drush_get_option('feedback', 1000)*60)/($now-$start))), 'ok');
            $start = $now;
          }
        }
      }
      $this->setMessage(\Drupal::translation()->formatPlural($values['num'], '1 node created.', 'Finished creating @count nodes'));
    }
    else {
      //@todo devel_generate_batch_content($form_state).
    }
  }

  public function handleDrushParams($args) {

    $add_language = drush_get_option('languages');
    if (!empty($add_language)) {
      $add_language = explode(',', str_replace(' ', '', $add_language));
      // Intersect with the enabled languages to make sure the language args
      // passed are actually enabled.
      $values['values']['add_language'] = array_intersect($add_language, array_keys(locale_language_list()));
    }

    $values['kill'] = drush_get_option('kill');
    $values['title_length'] = 6;
    $values['num'] = array_shift($args);
    $values['max_comments'] = array_shift($args);
    $all_types = array_keys(node_type_get_names());
    $default_types = array_intersect(array('page', 'article'), $all_types);
    $selected_types = _convert_csv_to_array(drush_get_option('types', $default_types));

    if (empty($selected_types)) {
      return drush_set_error('DEVEL_GENERATE_NO_CONTENT_TYPES', dt('No content types available'));
    }

    $values['node_types'] = drupal_map_assoc($selected_types);
    $node_types = array_filter($values['node_types']);

    if (!empty($values['kill']) && empty($node_types)) {
      return drush_set_error('DEVEL_GENERATE_INVALID_INPUT', dt('Please provide content type (--types) in which you want to delete the content.'));
    }

    return $values;
  }

  protected function contentKill($values) {
    $results = db_select('node', 'n')
      ->fields('n', array('nid'))
      ->condition('type', $values['node_types'], 'IN')
      ->execute();
    foreach ($results as $result) {
      $nids[] = $result->nid;
    }

    if (!empty($nids)) {
      entity_delete_multiple('node', $nids);
      $this->setMessage(t('Deleted %count nodes.', array('%count' => count($nids))));
    }
  }

  protected function develGenerateContentPreNode(&$results) {
    // Get user id.
    $users = $this->getUsers();
    $users = array_merge($users, array('0'));
    $results['users'] = $users;
  }

  /**
   * Create one node. Used by both batch and non-batch code branches.
   *
   * @param $num
   *   array of options obtained from devel_generate_content_form.
   */
  protected function develGenerateContentAddNode(&$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }
    $users = $results['users'];

    $node_type = array_rand(array_filter($results['node_types']));
    $type = node_type_load($node_type);
    $uid = $users[array_rand($users)];

    $edit_node = array(
      'nid' => NULL,
      'type' => $node_type,
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'status' => TRUE,
      'promote' => mt_rand(0, 1),
      'created' => REQUEST_TIME - mt_rand(0, $results['time_range']),
      'langcode' => $this->getLangcode($results),
    );
    if ($type->has_title) {
      // We should not use the random function if the value is not random
      if ($results['title_length'] < 2) {
        $edit_node['title'] = $this->createGreeking(1, TRUE);
      }
      else {
        $edit_node['title'] = $this->createGreeking(mt_rand(1, $results['title_length']), TRUE);
      }
    }
    else {
      $edit_node['title'] = '';
    }
    $node = entity_create('node', $edit_node);

    // A flag to let hook_node_insert() implementations know that this is a
    // generated node.
    $node->devel_generate = $results;

    // Populate all core fields on behalf of field.module
    DevelGenerateFieldBase::generateFields($node, 'node', $node->bundle());

    // See devel_generate_node_insert() for actions that happen before and after
    // this save.
    $node->save();
  }

  /*
 * Determine language based on $results.
 */
  protected function getLangcode($results) {
    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = language_default()->id;
    }
    return $langcode == 'en' ? Language::LANGCODE_NOT_SPECIFIED : $langcode;
  }

  protected function getUsers() {

    $users = array();
    $result = db_query_range("SELECT uid FROM {users}", 0, 50);
    foreach ($result as $record) {
      $users[] = $record->uid;
    }
    return $users;
  }

}