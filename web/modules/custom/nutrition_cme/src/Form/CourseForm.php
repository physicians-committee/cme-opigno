<?php

namespace Drupal\nutrition_cme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\opigno_module\Entity\OpignoModule;

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
    $form['disclosure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disclosure:'),
      '#required' => FALSE,
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
    if (!preg_match('/^[a-zA-Z0-9 ]+$/i', $form_state->getValue('disclosure'))) {
      $form_state->setErrorByName('disclosure', $this->t('Disclosure must be alphanumeric.'));
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

    $module = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Disclosure',
    ]);
    $module->save();

    $course->addContent($module, 'opigno_module_group');

    $content = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $module->id(),
      0,
      1
    );

    $content->save();

    $activity = OpignoActivity::create([
      'type' => 'opigno_slide',
      'name' => $form_state->getValue('course_name') . ' Disclosure',
    ]);
    if (!empty($form_state->getValue('disclosure'))) {
      $activity->opigno_body->value = $form_state->getValue('disclosure');
      $activity->opigno_body->format = 'basic_html';
    }
    $activity->save();

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$activity], $module);

  }

}