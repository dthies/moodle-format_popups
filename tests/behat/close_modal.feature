@format @format_popups @javascript @format_popups_close
Feature: Modals can be closed and update course page
  In order to complete course content
  As a student
  I need to close modals and updated course page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1        | popups | 0             | 5           | 1                |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section | completion | completionview |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 1       | 2          | 1              |
      | page       | Test page name         | Test page description         | C1     | page1       | 2       | 2          | 1              |
      | page       | Next page name         | Next page description         | C1     | page2       | 3       | 2          | 1              |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And I log in as "student1"
    And I am on "Course 1" course homepage

  Scenario: Open view in modal
    When I click on "Test page name" "link" in the "region-main" "region"
    Then I should see "Test page content"

  Scenario: Close page modal
    When I click on "Test page name" "link" in the "region-main" "region"
    And I click on "Close" "button" in the "Test page name" "dialogue"
    Then I should see "Done: View" in the "[data-region='activity-information'][data-activityname='Test page name']" "css_element"

  Scenario: Close choice  modal
    When I click on "Test choice name" "link" in the "region-main" "region"
    And I click on "Close" "button" in the "Test choice name" "dialogue"
    Then I should see "Done: View" in the "[data-region='activity-information'][data-activityname='Test choice name']" "css_element"
