# Reservation Overlap Validator

## Description

This Drupal 10 module validates that reservations for the same asset do not overlap in time. It provides a custom validation handler for reservation content types to prevent scheduling conflicts.

When a user creates or edits a reservation, the module checks all existing reservations for the same asset to ensure that the requested time slot is available. If a conflict is found, a detailed error message is displayed to the user, including a link to the conflicting reservation.

The module checks for two types of conflicts:
1.  Conflicts with existing reservations for the same asset.
2.  Conflicts within the same reservation if multiple time ranges are entered.

## Dependencies

* Drupal Core: ^10
* Node Module (part of Drupal Core)

## Installation

1.  Copy the `reservation_overlap_validator` directory to your Drupal site's `modules/custom` directory.
2.  Navigate to **Extend** in the Drupal admin interface (`/admin/modules`).
3.  Find the **Reservation Overlap Validator** module and check the box to enable it.
4.  Click **Install**.

## Configuration

This module is configured to work with a content type named `reservation`. The `reservation` content type must have the following fields:
* An entity reference field named `field_reservation_asset` that points to the asset being reserved.
* A date range field named `field_reservation_time_range` for the reservation period.

The validation is automatically applied to the `node_reservation_form` and `node_reservation_edit_form` forms.
