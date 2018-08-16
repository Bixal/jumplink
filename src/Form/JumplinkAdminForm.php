<?php

namespace Drupal\jumplink\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class JumplinkAdminForm.
 */
class JumplinkAdminForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jumplink_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jumplink.settings');

    $form['paragraph_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Machine Name'),
      '#description' => $this->t('The paragraph field the jumplinks should apply to'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => $config->get('paragraph_machine_name'),
    ];
    $form['paragraph_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph type (Optional)'),
      '#description' => $this->t('If you would only like jumplinks to apply to a certain paragraph type, enter the machine name here'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#default_value' => $config->get('paragraph_type'),
    ];
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#description' => $this->t('The field which the jumplink titles are based on'),
      '#weight' => '0',
      '#default_value' => $config->get('field_name')
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'jumplink.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('jumplink.settings');
    foreach ($form_state->getValues() as $key => $value) {
      // Ignore all parent keys which may be set
      if (
        $key === 'paragraph_machine_name' ||
        $key === 'paragraph_type' ||
        $key === 'field_name') {
          $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
