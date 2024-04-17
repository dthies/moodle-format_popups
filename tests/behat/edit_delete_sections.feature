@format @format_popups @format_popups_editdelete
Feature: Sections can be edited and deleted in popup activities format
  In order to rearrange my course contents
  As a teacher
  I need to edit and Delete topics

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | initsections |
      | Course 1 | C1        | topics | 0             | 5           | 1            |
      | Course 2 | C2        | topics | 0             | 1           | 0            |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 1       |
      | lesson     | Test lesson name       | Test lesson description       | C1     | lesson1     | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And I log in as "teacher1"

  Scenario: View the default name of the general section in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I edit the section "0"
    Then the field "Section name" matches value ""
    And I should see "General"

  Scenario: Edit the default name of the general section in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    And I should see "General" in the "General" "section"
    When I edit the section "0" and I fill the form with:
      | Section name      | This is the general section |
    Then I should see "This is the general section" in the "page" "region"

  Scenario: View the default name of the second section in popup activities format
    Given I am on "Course 2" course homepage with editing mode on
    When I edit the section "1"
    Then the field "Section name" matches value ""
    And I should see "New section"

  Scenario: Edit section summary in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I edit the section "2" and I fill the form with:
      | Description | Welcome to section 2 |
    Then I should see "Welcome to section 2" in the "page" "region"

  Scenario: Edit section default name in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I edit the section "2" and I fill the form with:
      | Section name      | This is the second section |
    Then I should see "This is the second section" in the "page" "region"
    And I should not see "Section 2" in the "region-main" "region"

  @javascript
  Scenario: Inline edit section name in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I set the field "Edit section name" in the "Section 1" "section" to "Midterm evaluation"
    Then I should not see "Section 1" in the "region-main" "region"
    And "New name for section" "field" should not exist
    And I should see "Midterm evaluation" in the "li#section-1" "css_element"
    And I am on "Course 1" course homepage
    And I should not see "Topic 1" in the "region-main" "region"
    And I should see "Midterm evaluation" in the "li#section-1" "css_element"

  Scenario: Deleting the last section in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I delete section "5"
    Then I should see "Are you absolutely sure you want to completely delete \"Section 5\" and all the activities it contains?"
    And I press "Delete"
    And I should not see "Section 5"
    And I should see "Section 4"

  Scenario: Deleting the middle section in popup activities format
    Given I am on "Course 1" course homepage with editing mode on
    When I delete section "4"
    And I press "Delete"
    Then I should not see "Section 4"
    And I should not see "Test lesson name"
    And I should see "Test choice name" in the "Section 5" "section"
    And I should see "Section 5"

  @javascript
  Scenario: Adding sections at the end of a popups format
    Given I am on "Course 1" course homepage with editing mode on
    When I click on "Add section" "link" in the "course-addsection" "region"
    Then I should see "New section" in the "New section" "section"
    And I should see "Test choice name" in the "Section 5" "section"

  @javascript
  Scenario: Adding sections in between in popups format
    Given I am on "Course 1" course homepage with editing mode on
    When I hover over the "Add section" "link" in the "Section 4" "section"
    And I click on "Add section" "link" in the "Section 4" "section"
    Then I should see "New section" in the "New section" "section"
    And I should not see "Test choice name" in the "New section" "section"
