Feature: Payment Initiation Workflow

  Payments service initiates and processes payments through various gateways
  So that payments are handled correctly

  Scenario: Initiate payment
    Given gateway returns a redirect URL
    When initiate payment
    Then payment is initiated
    And payment redirect URL is set up

  Scenario: Initiate payment with gateway not returning redirect URL
    Given gateway does not return a redirect URL
    When initiate payment
    Then payment is initiated
    And payment is marked as failed
    And payment did not succeed integration event is emitted

  Scenario: Initiated payment result is handled correctly
    Given initiated payment exists
    When mark payment as <payment_state>
    Then payment is marked as <payment_state>
    And payment <integration_event> integration event is emitted

    Examples:
      | payment_state | integration_event          |
      | authorized    | succeeded                  |
      | captured      | succeeded                  |
      | failed        | did not succeed            |
      | canceled      | did not succeed            |
