@local @local_transcript
Feature: Issue a transcript and let anyone verify it
  In order to prove what a student completed
  As a student and as an employer
  I need to issue a transcript with a code, and check that code without an account

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | idnumber |
      | ines     | Ines      | Duarte   | S-1001   |
    And the following "courses" exist:
      | fullname  | shortname | enablecompletion |
      | Biology 1 | BIO1      | 1                |
    And the following "course enrolments" exist:
      | user | course | role    |
      | ines | BIO1   | student |
    And the following config values are set as admin:
      | institution | Northfield College | local_transcript |
      | include     | graded             | local_transcript |
    And the following "grade items" exist:
      | itemname   | course | grademax |
      | Final exam | BIO1   | 100      |
    And the following "grade grades" exist:
      | gradeitem  | user | finalgrade |
      | Final exam | ines | 88.00      |

  @javascript
  Scenario: A student issues a transcript and a visitor verifies it, then a manager revokes it
    Given I log in as "ines"
    And I visit "/local/transcript/index.php"
    Then I should see "Biology 1"
    And I press "Issue a transcript"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Transcript issued. Its code is"
    And I click on "Verify a transcript" "link" in the "generaltable" "table"
    Then I should see "Valid"
    And I should see "This transcript was issued by Northfield College"
    And I should see "88.00"
    And I log out
    And I log in as "admin"
    And I navigate to "Users > Issued transcripts" in site administration
    And I click on "Ines Duarte" "link"
    And I click on "Revoke" "link"
    And I set the field "Reason (kept for staff, not shown on the verify page)" to "Wrong programme"
    And I press "Revoke"
    And I click on "Verify a transcript" "link" in the "generaltable" "table"
    Then I should see "This transcript was revoked on"
    And I should not see "Wrong programme"
    And I log out
    And I visit "/local/transcript/manage.php"
    Then I should see "Log in"

  Scenario: An unknown code is reported, without logging in
    When I visit "/local/transcript/verify.php?code=AAAA-BBBB-CCCC"
    Then I should see "Not found"
    And I should see "No transcript has this code"
