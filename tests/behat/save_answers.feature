@mod @mod_quiz @quizaccess @quizaccess_savenow
Feature: Save quiz answers at any time
  In order not to lose my work
  As a student
  I need to save my answers without leaving the page or finishing the attempt

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Student   | One      | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | student | C1     | student |
    And the following "activities" exist:
      | activity | name   | course | idnumber | navmethod  | savenow_enabled |
      | quiz     | Quiz 1 | C1     | quiz1    | free       | 1               |
      | quiz     | Quiz 2 | C1     | quiz2    | free       | 0               |
      | quiz     | Quiz 3 | C1     | quiz3    | sequential | 1               |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext    |
      | Test questions   | truefalse | TF1  | First question  |
      | Test questions   | truefalse | TF2  | Second question |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 2    |
    And quiz "Quiz 2" contains the following questions:
      | question | page |
      | TF1      | 1    |
    And quiz "Quiz 3" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 2    |

  @javascript
  Scenario: The button saves the answers and stays on the same page
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "First question" "question"
    When I press "Save my answers"
    Then I should see "Your answers have been saved."
    And I should see "First question"
    And I should not see "Second question"
    And "input[type=radio][value='1'][checked]" "css_element" should exist in the "First question" "question"
    And I should see "Answer saved" in the "First question" "question"

  @javascript
  Scenario: The keyboard shortcut saves the answers
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    And I press "Attempt quiz"
    And I click on "False" "radio" in the "First question" "question"
    When I press the ctrl s key
    Then I should see "Your answers have been saved."
    And "input[type=radio][value='0'][checked]" "css_element" should exist in the "First question" "question"

  @javascript
  Scenario: The button keeps the student on the same page with sequential navigation
    Given I am on the "Quiz 3" "mod_quiz > View" page logged in as "student"
    And I press "Attempt quiz"
    And I click on "True" "radio" in the "First question" "question"
    When I press "Save my answers"
    Then I should see "Your answers have been saved."
    And I should see "First question"
    And I should not see "Second question"
    And "input[type=radio][value='1'][checked]" "css_element" should exist in the "First question" "question"
    And I press "Next page"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Save my answers"
    And I should see "Your answers have been saved."
    And I should see "Second question"
    And I should not see "First question"
    And "input[type=radio][value='0'][checked]" "css_element" should exist in the "Second question" "question"

  @javascript
  Scenario: The button is not shown when the setting is disabled
    Given I am on the "Quiz 2" "mod_quiz > View" page logged in as "student"
    When I press "Attempt quiz"
    Then I should see "First question"
    And "Save my answers" "button" should not exist

  @javascript
  Scenario: A teacher can disable the button in the quiz settings
    Given I am on the "Quiz 1" "quiz activity editing" page logged in as "admin"
    And I expand all fieldsets
    When I set the field "Show a \"Save my answers\" button" to "No"
    And I press "Save and display"
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    And I press "Attempt quiz"
    Then "Save my answers" "button" should not exist
