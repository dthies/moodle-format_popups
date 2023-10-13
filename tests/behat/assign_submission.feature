@format @format_popups @javascript @format_popups_assign
Feature: Manage subission in modal
  In order to complete an assignment
  As a student
  I need to change submission with modal

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | popups | 0             | 5           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  Scenario: Add a submission
    Given the following "activity" exists:
      | activity                             | assign                |
      | course                               | C1                    |
      | name                                 | First assignment      |
      | submissiondrafts                     | 0                     |
      | section                              | 0                     |
      | assignsubmission_onlinetext_enabled  | 1                     |
      | assignsubmission_file_enabled        | 0                     |
      | submissiondrafts                     | 0                     |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "First assignment" "link" in the "region-main" "region"
    And I click on "Add submission" "button" in the "First assignment" "dialogue"
    And I set the following fields to these values:
      | Online text | Purple elephants |
    And I click on "Save changes" "button"
    Then I should see "Purple elephants"
    And I should see "Submitted for grading" in the "First assignment" "dialogue"

  Scenario: Remove a submission
    Given the following "activity" exists:
      | activity                             | assign                |
      | course                               | C1                    |
      | name                                 | First assignment      |
      | submissiondrafts                     | 0                     |
      | section                              | 0                     |
      | assignsubmission_onlinetext_enabled  | 1                     |
      | assignsubmission_file_enabled        | 0                     |
      | submissiondrafts                     | 0                     |
    And the following "mod_assign > submissions" exist:
      | assign            | user      | onlinetext       |
      | First assignment  | student1  | Purple elephants |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "First assignment" "link" in the "region-main" "region"
    And I click on "Remove submission" "button" in the "First assignment" "dialogue"
    And I click on "Continue" "button" in the "First assignment" "dialogue"
    Then I should not see "Purple elephants"
    And I should see "No submissions" in the "First assignment" "dialogue"
