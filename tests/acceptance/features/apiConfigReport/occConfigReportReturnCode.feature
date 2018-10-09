@api
Feature: generate a configreport via occ
  As an administrator
  I want to generate a configreport to file a support / help request
  So that administrator can check the server report 

  Scenario: admins generates a configreport
    Given the administrator has invoked occ command "configreport:generate"
    Then the command should have been successful
