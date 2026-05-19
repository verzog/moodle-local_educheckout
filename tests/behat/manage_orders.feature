@local @local_moodec
Feature: Moodec order management
  In order to support students
  As an admin
  I need to be able to view orders

  Background:
    Given I log in as "admin"

  Scenario: Admin can view the orders page
    When I visit "/local/moodec/manage_orders.php"
    Then I should see "Order management"
    And I should see "All"

  Scenario: Orders page shows status filter tabs
    When I visit "/local/moodec/manage_orders.php"
    Then I should see "Pending"
    And I should see "Paid"
    And I should see "Delivered"
    And I should see "Failed"
    And I should see "Cancelled"

  Scenario: Admin can filter orders by status
    When I visit "/local/moodec/manage_orders.php?status=pending"
    Then I should see "Order management"
