@api
Feature: generate a configreport via occ
  As an administrator
  I want to generate a configreport to file a support / help request
  So that an administrator or support person can know the system configuration

  Scenario: admin generates a configreport
    Given the administrator has invoked occ command "configreport:generate"
    Then the command should have been successful
    And the command output should contain the text '"basic"'
    And the command output should contain the text '"stats"'
    And the command output should contain the text '"config"'

  Scenario: admin generates a web configreport
    Given the administrator has invoked occ command "configreport:generate --web"
    Then the command should have been successful
    And the command output should contain the text '"basic"'
    And the command output should contain the text '"stats"'
    And the command output should contain the text '"config"'
    # The report from the CLI has `"phpinfo": []` (an empty array)
    # So check for the opening { that indicates that the --web option did produce
    # a config report with something in "phpinfo"
    And the command output should contain the text '"phpinfo": {'
    # The --web option also returns an array of environment variables,
    # so check that that exists and looks to have some content.
    And the command output should contain the text '"Environment": {'
