Feature: Campaign Management

  Donations service manages the lifecycle of donation campaigns
  So that campaigns can be created, updated, activated, and archived

  Scenario: Create campaign
    When create campaign with name "Test Campaign" and public title "Test Public Title"
    Then campaign is created with status "draft"

  Scenario: Update campaign name
    Given created campaign exists
    When update campaign name to "Updated Campaign Name"
    Then campaign name is updated to "Updated Campaign Name"

  Scenario: Update campaign public title
    Given created campaign exists
    When update campaign public title to "Updated Public Title"
    Then campaign public title is updated to "Updated Public Title"

  Scenario: Activate campaign from draft
    Given created campaign exists
    When activate campaign
    Then campaign is activated

  Scenario: Archive campaign from active
    Given created and activated campaign exists
    When archive campaign
    Then campaign is archived

  Scenario: Reactivate archived campaign
    Given created, activated, and archived campaign exists
    When activate campaign
    Then campaign is activated

  Scenario: Cannot archive campaign from draft
    Given created campaign exists
    When trying to archive campaign
    Then operation fails with error "Cannot transition from draft to archived."
