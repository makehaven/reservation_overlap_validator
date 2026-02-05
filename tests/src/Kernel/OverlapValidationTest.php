<?php

namespace Drupal\Tests\reservation_overlap_validator\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests reservation overlap validation.
 *
 * @group reservation_overlap_validator
 */
class OverlapValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'datetime',
    'datetime_range',
    'reservation_overlap_validator',
  ];

  /**
   * The item node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $item;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'field', 'node']);

    // Create reservation and item node types.
    NodeType::create(['type' => 'reservation', 'name' => 'Reservation'])->save();
    NodeType::create(['type' => 'item', 'name' => 'Item'])->save();

    // Create field_reservation_asset.
    FieldStorageConfig::create([
      'field_name' => 'field_reservation_asset',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_reservation_asset',
      'entity_type' => 'node',
      'bundle' => 'reservation',
      'label' => 'Asset',
    ])->save();

    // Create field_reservation_time_range.
    FieldStorageConfig::create([
      'field_name' => 'field_reservation_time_range',
      'entity_type' => 'node',
      'type' => 'daterange',
      'settings' => ['datetime_type' => 'datetime'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_reservation_time_range',
      'entity_type' => 'node',
      'bundle' => 'reservation',
      'label' => 'Time Range',
    ])->save();

    // Create an item.
    $this->item = Node::create([
      'type' => 'item',
      'title' => 'Test Item',
    ]);
    $this->item->save();
  }

  /**
   * Tests the overlap validation.
   */
  public function testOverlapValidation() {
    // 1. Create an existing reservation.
    // 10:00 to 12:00.
    $existing_reservation = Node::create([
      'type' => 'reservation',
      'title' => 'Existing Reservation',
      'field_reservation_asset' => $this->item->id(),
      'field_reservation_time_range' => [
        'value' => '2026-02-04T10:00:00',
        'end_value' => '2026-02-04T12:00:00',
      ],
      'status' => 1,
    ]);
    $existing_reservation->save();

    // 2. Prepare a new reservation that overlaps (11:00 to 13:00).
    $new_reservation = Node::create([
      'type' => 'reservation',
      'bundle' => 'reservation',
    ]);

    $form_state = new FormState();
    $form_state->setFormObject($this->container->get('entity_type.manager')->getFormObject('node', 'default')->setEntity($new_reservation));

    $values = [
      'field_reservation_asset' => [
        ['target_id' => $this->item->id()],
      ],
      'field_reservation_time_range' => [
        [
          'value' => new DrupalDateTime('2026-02-04T11:00:00'),
          'end_value' => new DrupalDateTime('2026-02-04T13:00:00'),
        ],
      ],
    ];
    $form_state->setValues($values);

    // Call the validation function.
    reservation_overlap_validator_validate([], $form_state);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors, 'Validation error should be set for overlapping reservation.');
  }

  /**
   * Tests non-overlapping reservations.
   */
  public function testNoOverlapValidation() {
    // 1. Create an existing reservation (10:00 to 12:00).
    $existing_reservation = Node::create([
      'type' => 'reservation',
      'title' => 'Existing Reservation',
      'field_reservation_asset' => $this->item->id(),
      'field_reservation_time_range' => [
        'value' => '2026-02-04T10:00:00',
        'end_value' => '2026-02-04T12:00:00',
      ],
      'status' => 1,
    ]);
    $existing_reservation->save();

    // 2. Prepare a new reservation that DOES NOT overlap (12:01 to 14:00).
    $new_reservation = Node::create([
      'type' => 'reservation',
      'bundle' => 'reservation',
    ]);

    $form_state = new FormState();
    $form_state->setFormObject($this->container->get('entity_type.manager')->getFormObject('node', 'default')->setEntity($new_reservation));

    $values = [
      'field_reservation_asset' => [
        ['target_id' => $this->item->id()],
      ],
      'field_reservation_time_range' => [
        [
          'value' => new DrupalDateTime('2026-02-04T12:01:00'),
          'end_value' => new DrupalDateTime('2026-02-04T14:00:00'),
        ],
      ],
    ];
    $form_state->setValues($values);

    // Call the validation function.
    reservation_overlap_validator_validate([], $form_state);

    $errors = $form_state->getErrors();
    $this->assertEmpty($errors, 'No validation error should be set for non-overlapping reservation.');
  }

}