Feature: Resolve identities from claims

  A claim is assigned to the first matching identity in this order:
  correlated source, email, legal identifier, and IBAN. If nothing matches, a new identity is created.

  Scenario Outline: Claim assignment selects the highest-priority identity match
    Given identity "correlated" has a claim with source "donation:don1"
    And identity "email" exists with email "email@example.com"
    And identity "legal-identifier" exists with legal identifier "12345678901"
    And identity "iban" exists with iban "EE382200221020145685"
    When claim is presented with "<correlated_sources>" correlated sources, email "<email>", legal identifier "<legal_identifier>", and iban "<iban>"
    Then claim is assigned to identity "<identity>"

    Examples:
      | case                              | correlated_sources | email               | legal_identifier | iban                  | identity         |
      | correlated source matches         | donation:don1      | email@example.com   | 12345678901      | EE382200221020145685  | correlated       |
      | email matches without correlation | no                 | email@example.com   | 12345678901      | EE382200221020145685  | email            |
      | legal identifier matches          | no                 | other@example.com   | 12345678901      | EE382200221020145685  | legal-identifier |
      | iban matches                      | no                 | other@example.com   | 12345678902      | EE382200221020145685  | iban             |
      | no value matches                  | no                 | other@example.com   | 12345678902      | DE89370400440532013000 | new              |

  Scenario Outline: Correlated source updates recalculate identity assignments
    Given claims with sources "<sources>" are presented with correlations "<initial_correlations>"
    When correlation "<correlation_update>" is applied
    Then claims are assigned to identity groups "<identity_groups>"

    # d1 is donation:d1 and p1 is payment:p1. In a correlation, p1->d1 means that
    # claim p1 declares source d1 as correlated. Commas join claims in one identity;
    # pipes separate identities. A correlation update uses + to add and - to remove a link.
    Examples:
      | case                                           | sources       | initial_correlations        | correlation_update | identity_groups |
      | added link merges separate identities          | d1,p1,d2,p2   | p1->d1,p2->d2               | +p1->d2            | d1,p1,d2,p2     |
      | added link within an identity changes nothing  | d1,p1,p2      | p1->d1,p2->p1               | +p2->d1            | d1,p1,p2        |
      | removed bridge splits an identity              | d1,p1,p2      | p1->d1,p2->p1               | -p1->d1            | d1\|p1,p2       |
      | removed redundant link changes nothing         | d1,p1,p2      | p1->d1,p2->p1,p2->d1        | -p2->d1            | d1,p1,p2        |
