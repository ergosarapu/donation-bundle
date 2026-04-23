Feature: One-Time Donation

  Donations service initiates and processes one-time donations
  So that donations are handled correctly

  Scenario: Initiate one-time donation
    When initiate one time donation
    Then donation is initiated
    And initiate payment integration command is sent

  Scenario: Successful payment results in accepted donation
    Given initiated donation exists
    When payment succeeds
    Then donation is marked as accepted

  Scenario: Unsuccessful payment results in failed donation
    Given initiated donation exists
    When payment fails
    Then donation is marked as failed
