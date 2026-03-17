Feature: Payment Import Workflow

  Payments service allows importing payments from external sources
  So that they can be accepted, rejected or reconciled with existing payment

  Scenario: Import single payment from file
    Given single_entry_private_debtor.camt.xml has been uploaded
    When import payments from file
    Then the imported payment is in review state

  Scenario: Import single entry is automatically reconciled
    Given single_entry_private_debtor.camt.xml has been uploaded
    And payment with same details as in single_entry_private_debtor.camt.xml already exists
    When import payments from file
    Then the imported payment is reconciled with existing payment

  Scenario: Import single entry is not reconciled
    Given single_entry_private_debtor.camt.xml has been uploaded
    And payment with different details from single_entry_private_debtor.camt.xml already exists
    When import payments from file
    Then the imported payment is not reconciled with existing payment
    And the imported payment is in review state
