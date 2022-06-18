# Changelog

All notable changes to `google-cloud-tasks-laravel` will be documented in this file

## v1.0.0-alpha - 2022-06-18

### Changes

Library has been changed to be more inline with the functionality of Laravel queues.

- Delays and backoffs should work like any other.
- Jobs will be re-dispatched instead of relying on the Google Cloud Tasks queue to retries.
- Commands added to handle queues.
- Queue monitor command can bring back the size of the Queue.

### Missing

I hope to add worker options for queues making it easier to customise things like memory usage and default retries as the worker mechanism for Google Cloud Tasks is different to running a CLI command.

## Laravel 9 - 2022-02-09

# Changes

Upgrades to Laravel 9

## 0.3.0 - 2020-09-19

- Adds `TaskStarted` and `TaskFinished` events.
- Internal improvement to extending the QueueManager with the cloud tasks and app engine task
- drivers.

## 0.2.0 - 2020-09-15

- updates to Laravel 8, drops 7 due to worker options class changing between frameworks.

## 0.1.0 - 2020-08-14

- initial release
