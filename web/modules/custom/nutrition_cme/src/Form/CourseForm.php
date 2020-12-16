<?php

namespace Drupal\nutrition_cme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\VerticalTabs
use Drupal\group\Entity\Group;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedLink;
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

    $form['course_form'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['course_info'] = [
      '#type' => 'fieldset',
      '#title' => t('Course'),
      '#collapsible' => TRUE,
      '#description' => t('Input course information.'),
      '#group' => 'course_form'
    ];

    $form['course_info']['course_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course Name:'),
      '#description'=> t('Name of the course.'),
      '#required' => TRUE,
      '#group' => 'course_form',
    ];

    $form['course_info']['course_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Course Description:'),
      '#description'=> t('Description of the course.'),
      '#required' => TRUE,
      '#group' => 'course_form',
    ];

    $form['disclosure_info'] = [
      '#type' => 'fieldset',
      '#title' => t('Disclosure'),
      '#collapsible' => TRUE,
      '#description' => t('Input disclosure information.'),
      '#group' => 'course_form'
    ];

    $form['disclosure_info']['disclosure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disclosure:'),
      '#required' => FALSE,
      '#group' => 'course_form'
    ];

    $form['disclosure_info']['content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content:'),
      '#required' => FALSE,
      '#group' => 'course_form'
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
    if (!preg_match('/^[a-zA-Z0-9 ]+$/i', $form_state->getValue('content'))) {
      $form_state->setErrorByName('content', $this->t('Content must be alphanumeric.'));
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

    $disclosure = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Disclosure',
    ]);

    $disclosure->save();

    $course->addContent($disclosure, 'opigno_module_group');

    $add_disclosure = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $disclosure->id(),
      0,
      1
    );

    $add_disclosure->save();

    $disclosure_activity = OpignoActivity::create([
      'type' => 'opigno_slide',
      'name' => $form_state->getValue('course_name') . ' Disclosure',
    ]);

    if (!empty($form_state->getValue('disclosure'))) {
      $disclosure_activity->opigno_body->value = $form_state->getValue('disclosure');
      $disclosure_activity->opigno_body->format = 'basic_html';
    }
    
    $disclosure_activity->save();

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$disclosure_activity], $disclosure);

    $content = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Content',
    ]);

    $content->save();

    $course->addContent($content, 'opigno_module_group');

    $add_content = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $content->id(),
      0,
      1
    );

    $add_content->save();

    $content_activity = OpignoActivity::create([
      'type' => 'opigno_slide',
      'name' => $form_state->getValue('course_name') . ' Content',
    ]);
    
    if (!empty($form_state->getValue('content'))) {
      $content_activity->opigno_body->value = $form_state->getValue('content');
      $content_activity->opigno_body->format = 'basic_html';
    }

    $content_activity->save();

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$content_activity], $content);

    $link = OpignoGroupManagedLink::createWithValues(
      $add_content->getGroupId(),
      $add_disclosure->id(),
      $add_content->id(),
      0
    );

    $link->save();

  }

}
