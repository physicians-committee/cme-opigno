<?php

namespace Drupal\nutrition_cme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;

/**
 * Contains \Drupal\nutrition_cme\Form\CourseForm.
 *
 * This class creates a form to input course information.
 */
class CourseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'course_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['course_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course Name:'),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[a-zA-Z0-9 ]+$/i', $form_state->getValue('course_name'))) {
      $form_state->setErrorByName('course_name', $this->t('Course name must be alphanumeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $course = Group::create(
      [
        'type' => 'learning_path',
        'label' => $form_state->getValue('course_name'),
      ]
    );
    $course->save();
    $module = entity_create('opigno_module', array(
        'label' => $form_state->getValue('course_name') . ' Disclosure',
      )
    );
    $module->save();
    $course->addContent($module, 'group_content_type_162f6c7e7c4fa');
  }

}
