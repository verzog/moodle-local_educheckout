@local @local_moodec
Feature: Moodec product management
  In order to sell courses
  As an admin
  I need to be able to manage products

  Background:
    Given the following "courses" exist:
      | fullname    | shortname |
      | Test Course | TC101     |
    And I log in as "admin"

  Scenario: Admin can view the product management page
    When I visit "/local/moodec/manage.php"
    Then I should see "Manage products"
    And I should see "Add product"

  @javascript
  Scenario: Admin can navigate to the add product form
    Given I visit "/local/moodec/manage.php"
    When I click on "Add product" "link"
    Then I should see "Add product"
    And "Save changes" "button" should exist

  Scenario: Admin can disable and re-enable a product
    Given the following "local_moodec > product" exist:
      | course | is_enabled |
      | TC101  | 1          |
    And I visit "/local/moodec/manage.php"
    Then I should see "Test Course"
    When I click on "Disable" "link"
    Then I should see "Disabled"
    When I click on "Enable" "link"
    Then I should see "Enabled"
