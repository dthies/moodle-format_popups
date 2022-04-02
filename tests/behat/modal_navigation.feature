@format @format_popups @javascript @format_popups_book
Feature: Navigate book modal
  In order to view course content
  As a student
  I need to open resources in modals

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | popups | 0             | 5           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book              | Test book description         | C1     | book1       | 1       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Test book" "link" in the "region-main" "region"
    And I set the following fields to these values:
      | Chapter title | First chapter |
      | Content | First chapter |
    And I press "Save changes"
    And I click on "Add new chapter after \"First chapter\"" "link"
    And I set the following fields to these values:
      | Chapter title | Second chapter |
      | Content | Second chapter |
    And I press "Save changes"
    And I click on "Add new chapter after \"Second chapter\"" "link"
    And I set the following fields to these values:
      | Chapter title | Third chapter |
      | Content | Third chapter |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage

  Scenario: Open book in modal
    When I click on "Test book" "link" in the "region-main" "region"
    Then I should see "First chapter"
    And I follow "Next"
    And I should see "Second chapter"

  Scenario: Exit book modal
    When I click on "Test book" "link" in the "region-main" "region"
    And I follow "Next"
    And I follow "Next"
    And I follow "Exit book"
    And I should see "Topic 1"
