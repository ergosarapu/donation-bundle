Feature: Recurring Donation

  Donations service sets up recurring donations
  So that donations can be collected on a recurring basis automatically

  Scenario: Initiate recurring donation
    When initiate recurring donation
    Then donation is initiated for the renewal
    And recurring plan is initiated with the donation as initial donation
    And initiate payment integration command is sent with request to store payment method

  Scenario: Activate recurring plan
    Given initiated recurring plan exists
    When usable payment method is created
    Then recurring plan is marked as activated

  Scenario: Fail recurring plan when creating unusable payment method
    Given initiated recurring plan exists
    When unusable payment method is created
    Then recurring plan is marked as failed

  Scenario: Fail recurring plan when payment method gets unusable
    Given activated recurring plan exists
    When payment method gets unusable
    Then recurring plan is marked as failed

  Scenario: Recurring plan renewed successfully
    Given activated recurring plan exists
    When recurring plan is due for renewal
    Then recurring plan renewal is initiated
    And donation is initiated for the renewal
    And initiate payment integration command is sent with request to use payment method
    When payment succeeds
    Then donation is marked as accepted
    And recurring plan renewal is completed

  Scenario: Failing recurring plan is activated again after failed renewal
    Given activated recurring plan exists
    When recurring plan is due for renewal
    Then recurring plan renewal is initiated
    And donation is initiated for the renewal
    And initiate payment integration command is sent with request to use payment method
    When payment fails
    Then donation is marked as failed
    And recurring plan is marked as failing
    When recurring plan is re-activated
    Then recurring plan is marked as activated
    When recurring plan is due for renewal
    Then recurring plan renewal is initiated
    And donation is initiated for the renewal
    And initiate payment integration command is sent with request to use payment method
    When payment succeeds
    Then donation is marked as accepted
    And recurring plan renewal is completed