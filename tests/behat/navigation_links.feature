@format @format_popups @javascript @format_popups_links
Feature: Navigation links
  In order to view course content
  As a student
  I need to use navigation links in modal

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | addnavigation |
      | Course 1 | C1        | popups | 0             | 5           | 0             |
      | Course 2 | C2        | popups | 0             | 5           | 1             |
    And the following "activities" exist:
      | activity   | name              | intro                         | course | section |
      | page       | Test page 1       | Test page description         | C1     | 1       |
      | page       | Test page 2       | Test page description         | C2     | 1       |
      | choice     | Test choice 1     | Test choice description       | C1     | 2       |
      | choice     | Test choice 2     | Test choice description       | C2     | 2       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student1 | C2     | student        |
      | teacher1 | C1     | editingteacher |

  Scenario: Links not shown
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "Test page 1" "link" in the "region-main" "region"
    Then I should not see "Test choice 1" in the "Test page 1" "dialogue"

  @test
  Scenario: Follow navigation links
    Given I log in as "student1"
    And I am on "Course 2" course homepage
    When I click on "Test page 2" "link" in the "region-main" "region"
    And I click on "Test choice 2" "link" in the "Test page 2" "dialogue"
    Then I should see "Test page 2" in the "Test choice 2" "dialogue"
