Feature: Build identities from connected claims

  An identity consists of claims connected by correlated sources, email, legal identifier, or IBAN.
  A new claim joins the first matching identity in that order. A claim without a connection creates
  a new identity.

  Scenario Outline: A claim joins the highest-priority matching identity
    Given claim "correlated" has source "donation:don1"
    And claim "email" has email "email@example.com"
    And claim "legal-identifier" has legal identifier "12345678901"
    And claim "iban" has iban "EE382200221020145685"
    When a claim is presented with correlated sources "<correlated_sources>", email "<email>", legal identifier "<legal_identifier>", and iban "<iban>"
    Then the claim belongs to identity "<component>"

    Examples:
      | case                              | correlated_sources | email               | legal_identifier | iban                   | component        |
      | correlated source matches         | donation:don1      | email@example.com   | 12345678901      | EE382200221020145685   | correlated       |
      | email matches without correlation | no                 | email@example.com   | 12345678901      | EE382200221020145685   | email            |
      | legal identifier matches          | no                 | other@example.com   | 12345678901      | EE382200221020145685   | legal-identifier |
      | iban matches                      | no                 | other@example.com   | 12345678902      | EE382200221020145685   | iban             |
      | no value matches                  | no                 | other@example.com   | 12345678902      | DE89370400440532013000 | new              |

  Scenario Outline: Correlated source updates recalculate identities
    Given claims with sources "<sources>" have correlations "<initial_correlations>"
    When correlation "<correlation_update>" is applied
    Then identities contain claim groups "<identity_groups>"

    # d1 is donation:d1 and p1 is payment:p1. In a correlation, p1->d1 means that
    # claim p1 declares source d1 as correlated. Commas join claims in one identity;
    # pipes separate identities. A correlation update uses + to add and - to remove a link.
    Examples:
      | case                                           | sources       | initial_correlations        | correlation_update | identity_groups |
      | added link merges separate identities          | d1,p1,d2,p2   | p1->d1,p2->d2               | +p1->d2            | d1,p1,d2,p2     |
      | added link within an identity changes nothing  | d1,p1,p2      | p1->d1,p2->p1               | +p2->d1            | d1,p1,p2        |
      | removed bridge splits an identity              | d1,p1,p2      | p1->d1,p2->p1               | -p1->d1            | d1\|p1,p2       |
      | removed redundant link changes nothing         | d1,p1,p2      | p1->d1,p2->p1,p2->d1        | -p2->d1            | d1,p1,p2        |
