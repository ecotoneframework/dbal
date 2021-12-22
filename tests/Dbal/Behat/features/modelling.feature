Feature: activating as aggregate order entity

  Scenario: I order with transaction a product with failure, so the order should never be committed to database
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\Transaction"
    When I transactionally order "milk"
    Then there should 0 registered orders

  Scenario: I order with transaction a product with failure, so the order should never be comitted to database using asynchronous consumer
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction"
    When I transactionally order "milk"
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 0 registered orders
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 1 registered orders

  Scenario: Application exception handling
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\DeadLetter"
    When I order "coffee"
    And I call pollable endpoint "orderService"
    Then there should be 0 orders
    And I call pollable endpoint "orderService"
    Then there should be 0 orders
    And there should 1 error message in dead letter
    When all error messages are replied
    Then there should 0 error message in dead letter
    When I call pollable endpoint "orderService"
    And there should be 1 orders

  Scenario: Provide support for ORM
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\ORM"
    When I register person with id 100 and name "Johny"
    Then there person with id 100 should be named "Johny"
