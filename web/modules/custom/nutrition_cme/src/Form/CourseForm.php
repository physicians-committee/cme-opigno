<?php

namespace Drupal\nutrition_cme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\VerticalTabs;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\media\Entity\Media;
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
      '#type' => 'details',
      '#title' => t('Course'),
      '#collapsible' => TRUE,
      '#description' => t('Enter the course information.'),
      '#group' => 'course_form'
    ];

    $form['course_info']['course_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course Name:'),
      '#description'=> t('Name of the course.'),
      '#required' => TRUE,
    ];

    $form['course_info']['course_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Course Description:'),
      '#description'=> t('Description of the course.'),
      '#required' => TRUE,
      '#format' => 'basic_html',
    ];

    $form['course_info']['course_category'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Course Category'),
      '#description' => $this->t('Select a category for the course.'),
      '#selection_settings' => [
        'target_bundles' => ['learning_path_category'],
      ],
    ];

    $form['course_info']['course_image'] = [
      '#type' => 'managed_file',
      '#title' => t('Course Image'),
      '#upload_validators' => array(
          'file_validate_extensions' => array('gif png jpg jpeg'),
          'file_validate_size' => array(25600000),
      ),
      '#upload_location' => 'public://course-images',
   ];

    $form['disclosure_info'] = [
      '#type' => 'details',
      '#title' => t('Disclosure'),
      '#collapsible' => TRUE,
      '#description' => t('Input disclosure information.'),
      '#group' => 'course_form'
    ];

    $form['disclosure_info']['disclosure'] = [
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $course_image = $form_state->getValue('course_image', 0);

    if (isset($course_image[0]) && !empty($course_image[0])) {
      $file = File::load($course_image[0]);
      $file->setPermanent();
      $file->save();
      $media = Media::create([
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'name' => $form_state->getValue('course_name'),
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $form_state->getValue('course_name'),
        ],
      ]);
      $media->save();
    }

    $course_description = $form_state->getValue('course_description');

    $course = Group::create(
      [
        'type' => 'learning_path',
        'label' => $form_state->getValue('course_name'),
        'field_learning_path_description' => ['value' => $course_description['value'], 'format' => $course_description['format']],
        'field_learning_path_category' => $form_state->getValue('course_category'),
      ]
    );

    if (isset($media)) {
      $course->field_learning_path_media_image->target_id = $media->id();
    }

    $course->save();

    $profession = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Profession',
    ]);

    $profession->save();

    $course->addContent($profession, 'opigno_module_group');

    $add_profession = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $profession->id(),
      0,
      1
    );

    $add_profession->save();

    $profession_activity = OpignoActivity::load(34);

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$profession_activity], $profession);

    $disclosure_default = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Disclosure - Default',
    ]);

    $disclosure_default->save();

    $course->addContent($disclosure_default, 'opigno_module_group');

    $add_disclosure_default = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $disclosure_default->id(),
      0,
      1
    );

    $add_disclosure_default->save();

    $disclosure_activity_default = OpignoActivity::create([
      'type' => 'opigno_slide',
      'name' => $form_state->getValue('course_name') . ' Disclosure - Default',
    ]);

    if (!empty($form_state->getValue('disclosure'))) {
      $disclosure_activity_default->opigno_body->value = $form_state->getValue('disclosure') . ' default';
      $disclosure_activity_default->opigno_body->format = 'basic_html';
    }
    
    $disclosure_activity_default->save();

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$disclosure_activity_default], $disclosure_default);

    $disclosure_other = OpignoModule::create([
      'type' => 'opigno_module',
      'name' => $form_state->getValue('course_name') . ' Disclosure - Other',
    ]);

    $disclosure_other->save();

    $course->addContent($disclosure_other, 'opigno_module_group');

    $add_disclosure_other = OpignoGroupManagedContent::createWithValues(
      $course->id(),
      'ContentTypeModule',
      $disclosure_other->id(),
      0,
      1
    );

    $add_disclosure_other->save();

    $disclosure_activity_other = OpignoActivity::create([
      'type' => 'opigno_slide',
      'name' => $form_state->getValue('course_name') . ' Disclosure - Other',
    ]);
    
    if (!empty($form_state->getValue('disclosure'))) {
      $disclosure_activity_other->opigno_body->value = $form_state->getValue('disclosure') . ' other';
      $disclosure_activity_other->opigno_body->format = 'basic_html';
    }

    $disclosure_activity_other->save();

    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule([$disclosure_activity_other], $disclosure_other);

    $link1 = OpignoGroupManagedLink::createWithValues(
      $add_profession->getGroupId(),
      $add_profession->id(),
      $add_disclosure_default->id(),
      0,
      serialize(['34','34-0'])
    );

    $link1->save();

    $link2 = OpignoGroupManagedLink::createWithValues(
      $add_profession->getGroupId(),
      $add_profession->id(),
      $add_disclosure_other->id(),
      0,
      serialize(['34','34-1'])
    );

    $link2->save();

  }

}
