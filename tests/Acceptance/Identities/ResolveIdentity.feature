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
    When a Claim with email "donor@example.com", iban "EE471000001020145685", legal identifier "38001085718", person name "John" "Doe" and raw name "John Doe" is presented with sufficient evidence
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
    Given an Identity with email "donor@example.com" and <identity_attribute> <existing_value> exists
    When a Claim with email "donor@example.com" and <claim_attribute> <conflicting_value> is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is merge conflict

    Examples:
      | identity_attribute | existing_value | claim_attribute  | conflicting_value |
      | legal identifier   | "38001085718"  | legal identifier | "49002010976"     |
      | person name        | "John" "Doe"   | person name      | "Jane" "Smith"    |
      | legal identifier   | "12345678"     | legal identifier | "87654322"        |

  Scenario Outline: Claim with conflicting legal identifier type presented to existing Identity results merge conflict
    Given an Identity with email "donor@example.com" and legal identifier <existing_legal_identifier> exists
    When a Claim with email "donor@example.com" and legal identifier <claimed_legal_identifier> is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is merge conflict

    Examples:
      | existing_legal_identifier | claimed_legal_identifier |
      | "38001085718"             | "12345678"               |
      | "12345678"                | "38001085718"            |

  Scenario: Claim presented with multiple matching Identity is marked for review
    Given an Identity with email "donor@example.com" exists
    And another Identity with iban "EE471000001020145685" exists
    When a Claim with email "donor@example.com" and iban "EE471000001020145685" is presented with sufficient evidence
    Then Claim is marked for review
    And Claim review reason is multiple identity matches
