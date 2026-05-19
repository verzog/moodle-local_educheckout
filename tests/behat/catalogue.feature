@local @local_educheckout
Feature: EduCheckout storefront catalogue is reachable
  In order to buy courses
  As a user
  I need to be able to open the course store

  Scenario: A logged in user can view the course store
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | buyer    | Bea       | Buyer    | buyer@example.com |
    And I log in as "buyer"
    When I visit "/local/educheckout/index.php"
    Then I should see "Course store"
