Feature: activating as aggregate order entity

  Scenario: I order with transaction a product with failure, so the order should never be placed
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\Transaction"
    When I transactionally order "milk"
    When I active receiver "placeOrderEndpoint"