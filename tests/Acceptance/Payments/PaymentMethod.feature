Feature: Payment Method request, store and usage workflow 

  Payments service requests, stores and allows usage of payment methods
  So that future payments can be processed without user input

  Scenario: Initiate payment with request to store payment method
    Given gateway returns a redirect URL
    When initiate payment with request to store payment method
    And mark payment as <payment_state> with <method_result> payment method result
    Then payment is marked as <payment_state>
    And <method_state> payment method is stored
    
    Examples:
      | payment_state  | method_result | method_state |
      | captured       | usable        | usable       |
      | captured       | no            | unusable     |
      | captured       | unusable      | unusable     |
      | failed         | usable        | unusable     |
      | failed         | no            | unusable     |
      | failed         | unusable      | unusable     |

  Scenario: Payment capture with usable stored payment method
    Given usable payment method exists
    And gateway <gateway_result> payment with <method_result> payment method result
    When initiate payment using stored payment method
    Then payment is initiated
    And payment method use is permitted
    And payment is reserved for gateway call
    And payment is marked as <payment_result>
    And stored payment method is <method_state>

    Examples:
      | gateway_result   | method_result | method_state | payment_result |
      | captures         | no            | usable       | captured       |
      | captures         | usable        | usable       | captured       |
      | captures         | unusable      | unusable     | captured       |
      | fails to capture | no            | usable       | failed         |
      | fails to capture | usable        | usable       | failed         |
      | fails to capture | unusable      | unusable     | failed         |

  Scenario: Payment capture fails with unusable stored payment method
    Given unusable payment method exists
    When initiate payment using stored payment method
    Then payment is initiated
    And payment method use is rejected
    And payment is marked as failed

  Scenario: Payment fails when payment method does not exist
    Given payment method does not exist
    When initiate payment using stored payment method
    Then payment is initiated
    And payment is marked as failed

  Scenario: Payment captured without stored payment method
    Given gateway returns a redirect URL
    When initiate payment
    And mark payment as captured
    Then payment is marked as captured
    And no payment method is stored