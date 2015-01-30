Feature: Stations Controller

  Scenario: request index
    When I request "stations"
    Then the response status code should be 200
    And the response is JSON
    And the type is "array"

  Scenario: request a valid stationid
    When I request "stations/1"
    Then the response status code should be 200
    And the response is JSON
    And the type is "object"

  Scenario: request an invalid stationid
    When I request "stations/666"
    Then the response status code should be 404
    And the response is JSON
    And the type is "object"
    # And the error object properties are..

  Scenario: request detectors for a valid stationid
    When I request "stations/1/detectors"
    Then the response status code should be 200
    And the response is JSON
    And the type is "array"

  Scenario: request detectors for an invalid stationid
    When I request "stations/666/detectors"
    Then the response status code should be 404
    And the response is JSON
    And the type is "object"
    # And the error object properties are..

  Scenario: request related onramp for valid stationid
    When I request "stations/1/relatedonramps"
    Then the response status code should be 200
    And the response is JSON
    And the type is "object"
    # And the response object properties are...

  Scenario: request related onramp for an invalid stationid
    When I request "stations/666/relatedonramps"
    Then the response status code should be 404
    And the response is JSON
    And the type is "object"
    # And the error object properties are..

  Scenario: request detectors for a valid stationid
    When I request "stations/1/detectors"
    Then the response status code should be 200
    And the response is JSON
    And the type is "array"

  Scenario: request detectors for an invalid stationid
    When I request "stations/666/detectors"
    Then the response status code should be 404
    And the response is JSON
    And the type is "object"
    # And the error object properties are..