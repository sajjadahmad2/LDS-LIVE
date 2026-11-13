# LDS-LIVE Architecture

## Overview

LDS-LIVE is a Laravel 10 application for campaign management, agent eligibility, lead/contact processing, and delivery to GoHighLevel (GHL). The reviewed source uses Laravel controllers, Eloquent models, queued jobs, routes, Blade views, and application helper functions.

## Main domain entities

| Entity / model | Responsibility visible in the code |
|---|---|
| Agent | Agent profile and relationships to states, carriers, lead types, campaigns, and contacts |
| Campaign | Campaign definition and lead type |
| CampaignAgent | Agent-to-campaign configuration, including priority and weightage |
| AgentLeadType | Per-agent lead-type configuration and delivery limits |
| AgentState | State eligibility associated with an agent and lead type |
| AgentCarrierType | Carrier eligibility associated with an agent and lead type |
| Contact | Contact/lead record and relationships to campaign, agent, and user |
| ProccessContact | Intermediate contact-processing record; spelling retained from source |
| ReserveContact | Contact record used by the reserve workflow |
| Job/log records | Operational trace of webhook and processing activity |

Confirm exact columns, constraints, and indexes against database migrations before changing the schema.

## Processing flow

1. **Inbound event:** A webhook controller receives an event and extracts identifiers and lead data, such as contact ID, state, event type, and campaign identifier.
2. **Event classification:** Contact-creation and survey-submission events follow separate controller paths.
3. **Logging:** Processing activity is recorded in job/log records.
4. **Queued processing:** The survey workflow dispatches a Laravel job for asynchronous processing.
5. **Eligibility:** Campaign agents are loaded with agent lead-type settings and contact counts. Assignment logic considers lead type, state context, daily/monthly/total limits, campaign priority, and weightage counters.
6. **Intermediate persistence:** Contact data is saved to the intermediate processing record.
7. **Final contact and handoff:** The application prepares and stores the contact payload, then invokes GHL handoff logic.
8. **Reserve handling:** When eligible agents cannot be found, the code includes a reserve-contact path.

This is a source-level outline, not a guarantee every branch is defect-free or every third-party request succeeds.

## Administrative functionality

The inspected controllers and routes include dashboard statistics, campaign and agent management, lead-type settings, contact review/reassignment, user management, reserve-contact handling, and processing-log review. Protect these functions with authentication and appropriate role/ownership checks.

## External integrations

### GoHighLevel

The processing implementation prepares contact data and sends it to GHL. Confirm the active endpoint, authentication method, custom-field mapping, retry behavior, and rate-limit handling from deployment configuration before publishing a detailed API reference.

### External Medicare agent directory

The webhook controller contains a request to an external agent directory using a state abbreviation. The response is expected to include an agents collection, which the application uses to match agent email addresses.

## Time handling

The application timezone in config/app.php is America/Chicago. Contact model accessors also format timestamps in that timezone. Keep this consistent when reviewing date-based limits, dashboard reporting, and queue processing.

## Implementation caveats

- Campaign-agent priority and weightage are present in the current code; do not document a different distribution algorithm unless the deployed implementation is verified.
- Queue configuration, worker supervision, retries, and failed-job monitoring are operational requirements.
- app/Helpers/helpers.php is autoloaded by Composer and should be reviewed when tracing payload creation, lead-type mapping, logging, and integrations.
- ProccessContact is misspelled in the existing model name. It is referenced by the application, so renaming it requires coordinated code and database changes.
