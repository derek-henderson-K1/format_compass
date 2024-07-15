Feature: Course Format Terminology

  Background:
    Given I am logged in as a "teacher"
    And the following "courses" exist:
      | Fullname        | Shortname | Format            |
      | Sample Course 1 | SC1       | topics            |

  @termforlessonstab
  Scenario: Verify terminology for Lesson tab
    When I edit the course "Sample Course 1"
    And I set the course format option "termforlessonstab" to "Lessons"
    And I save the course changes
    Then I should see "LESSONS" in the course index

    When I edit the course "Sample Course 1"
    And I set the course format option "termforlessonstab" to "Units"
    And I save the course changes
    Then I should see "UNITS" in the course index

  @termforassessmentstab
  Scenario: Verify terminology for Assessment tab
    When I edit the course "Sample Course 1"
    And I set the course format option "termforassessmentstab" to "Assessments"
    And I save the course changes
    Then I should see "ASSESSMENTS" in the course index

    When I edit the course "Sample Course 1"
    And I set the course format option "termforassessmentstab" to "Graded activities"
    And I save the course changes
    Then I should see "GRADED ACTIVITIES" in the course index