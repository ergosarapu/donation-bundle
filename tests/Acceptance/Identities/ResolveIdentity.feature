Feature: Resolve Identities based on presented Claims

  Identities service accepts Claims and merges to Identity
  So that identities database is built from the presented Claims

  Scenario: Claim presented with no matching Identity creates new Identity
    Given no Identity exists
    When a Claim with email "donor@example.com" is presented with sufficient evidence
    Then Claim is resolved
    And a new Identity is created

  Scenario: Claim presented with single matching Identity is merged into Identity and resolved
    Given an Identity with email "donor@example.com" exists
    When a Claim with email "donor@example.com", iban "EE471000001020145685", national id code "38001085718", person name "John" "Doe" and raw name "John Doe" is presented with sufficient evidence
    Then Claim is resolved
    And Claim is merged into existing Identity

  Scenario: Presenting Claim with same data results in single Identity
    Given no Identity exists
    And identity projection is not updating
    When a Claim with email "donor@example.com" is presented with sufficient evidence
    Then Claim is resolved
    And a new Identity is created
    When a Claim with email "donor@example.com" is presented with sufficient evidence
    Then Claim is resolved
    And Claim is merged into existing Identity

  Scenario Outline: Claim presented with single matching Identity results merge conflict and is marked for review
    Given an Identity with email "donor@example.com" and <attribute> <existing_value> exists
    When a Claim with email "donor@example.com" and <attribute> <conflicting_value> is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is merge conflict

    Examples:
      | attribute        | existing_value | conflicting_value |
      | national id code | "38001085718"  | "49002010976"     |
      | person name      | "John" "Doe"   | "Jane" "Smith"    |
      | org reg code     | "12345678"     | "87654322"        |

  Scenario Outline: Claim with conflicting identity code type presented to existing Identity results merge conflict
    Given an Identity with email "donor@example.com" and <existing_attribute> <existing_value> exists
    When a Claim with email "donor@example.com" and <claimed_attribute> <claimed_value> is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is merge conflict

    Examples:
      | existing_attribute | existing_value | claimed_attribute | claimed_value |
      | national id code   | "38001085718"  | org reg code      | "12345678"    |
      | org reg code       | "12345678"     | national id code  | "38001085718" |

  Scenario: Claim presented with multiple matching Identity is marked for review
    Given an Identity with email "donor@example.com" exists
    And another Identity with iban "EE471000001020145685" exists
    When a Claim with email "donor@example.com" and iban "EE471000001020145685" is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is multiple identity matches
