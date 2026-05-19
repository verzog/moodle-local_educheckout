@local @local_moodec
Feature: Moodec category management
  In order to organise products
  As an admin
  I need to be able to create and delete categories

  Background:
    Given I log in as "admin"

  Scenario: Admin can view the category management page
    When I visit "/local/moodec/category_manage.php"
    Then I should see "Manage categories"
    And I should see "Add category"

  Scenario: Admin can create a category
    Given I visit "/local/moodec/category_manage.php"
    When I set the field "Name" to "Compliance"
    And I press "Save changes"
    Then I should see "Compliance"

  Scenario: Admin can delete a category
    Given I visit "/local/moodec/category_manage.php"
    And I set the field "Name" to "ToDelete"
    And I press "Save changes"
    And I should see "ToDelete"
    When I click on "Delete" "link" in the "ToDelete" "table_row"
    And I press "Continue"
    Then I should not see "ToDelete"
