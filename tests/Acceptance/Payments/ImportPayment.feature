Feature: Payment Import Workflow

  Payments service allows importing payments from external sources
  So that they can be reviewed, accepted, rejected or reconciled with existing payment

  Scenario: Import payment from file
    Given payment import file has been uploaded
    When import payments from file
    Then payment is imported with import status Pending
