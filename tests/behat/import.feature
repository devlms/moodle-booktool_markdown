@mod @mod_book @booktool @booktool_markdown
Feature: Import Markdown into a book
  In order to reuse notes written in Markdown
  As a teacher
  I need to turn Markdown text into book chapters

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name       | course |
      | book     | Study book | C1     |

  Scenario: A teacher pastes Markdown and gets one chapter per heading
    Given I am on the "Study book" "book activity" page logged in as "teacher1"
    When I navigate to "Import from Markdown" in current page administration
    And I set the field "Markdown text" to multiline:
      """
      # Week 1
      Read **chapter one**.

      ## Exercises
      Do the first three.

      # Week 2
      Read chapter two.
      """
    And I press "Import"
    Then I should see "3 chapters were added to the end of the book."
    And I should see "Read chapter one."
    And I should see "Week 1" in the "Table of contents" "block"
    And I should see "Exercises" in the "Table of contents" "block"
    And I should see "Week 2" in the "Table of contents" "block"

  Scenario: The form asks for text or a file
    Given I am on the "Study book" "book activity" page logged in as "teacher1"
    When I navigate to "Import from Markdown" in current page administration
    And I press "Import"
    Then I should see "Paste some Markdown or choose a file."

  Scenario: Students do not get the import page
    Given I am on the "Study book" "book activity" page logged in as "student1"
    Then "Import from Markdown" "link" should not exist
