# Cockpit CMS Moderation Add-on

This addon extends Cockpit CMS core functionality by introducing the possibility to have moderation of collections and singletons. It means that its possible to create collections and singletons with a status (Unpublished, Draft or Published) affecting the way that entries are retrieved:

- **Unpublished** - Any collection entry or singleton in unpublished state will be filtered out.
- **Draft** - Any collection entry or singleton in Draft that doesn't have a previous revision in published status will be also filtered out. If there is a previous revision with published status the revision will be returned instead. However on a scenario that we have a published > unpublished > draft sequence no entry will be returned.
- **Published** - They are always returned.

## Installation

### Manual

Download [latest release](https://github.com/pauloamgomes/CockpitCMS-Moderation) and extract to `COCKPIT_PATH/addons/Moderation` directory

### Git

```sh
git clone https://github.com/pauloamgomes/CockpitCMS-Moderation.git ./addons/Moderation
```

### Cockpit CLI

```sh
php ./cp install/addon --name Moderation --url https://github.com/pauloamgomes/CockpitCMS-Moderation.git
```

### Composer

1. Make sure path to cockpit addons is defined in your projects' _composer.json_ file:

  ```json
  {
      "name": "MY_PROJECT",
      "extra": {
          "installer-paths": {
              "cockpit/addons/{$name}": ["type:cockpit-module"]
          }
      }
  }
  ```

2. In your project root run:

  ```sh
  composer require pauloamgomes/cockpitcms-moderation
  ```

---

## Configuration

To use the main functionality of the addon no extra configuration is required.
To use the preview mode (Draft entries will be also returned) is required to configure an API key
on the addon settings page. You can use the moderation api key in your requests like:

```
http://your-cockpit-site/api/collections/get/<name>?token=<api-key>&previewToken=<moderation-api-key>
```

Additional addon settings are available at: http://your-cockpit-site/settings/moderation

### Permissions

The following permissions (ACL's) are defined:

* **manage** - access to all moderation states and addons settings page
* **publish** - can change entries to Published state
* **unpublish** - can change entries to Unpublished state

Example of configuration for 3 groups of editors where `editor` can only create/update entries to `Draft` state, `approver` can create/update `Draft` and move to `Published` state, and finally `manager` can publish and unpublish entries.

```yaml
groups:
  editor:
  approver:
    moderation:
      publish: true
  manager:
    moderation:
      publish: true
      unpublish: true
```

By default admins have super access, any other groups that have not the permissions specificed in the configuration, can only create/edit
entries only in Draft mode.

### Scheduling

Scheduling is supported since version v1.3, to enable scheduling add a new entry in the config.yml like below:

```yaml
moderation:
  schedule:
    - page
    - article
```
above configuration enables scheduling on collections page and article, if you want to enable for all collections by default use:

```yaml
moderation:
  schedule: *
```

If using scheduling, its required to provide a `schedule` permission for non admin roles:

```yaml
groups:
  editor:
    moderation:
      schedule: true
```
The Scheduling just defines in Cockpit the operation and date/time for a specific collection entry, to run the scheduling is required
to invoke a REST API endpoint, that endpoing can be invoked using cron or other mechanism. By default when executed, it will run against
all scheduled entries between a range of time in the past (default of 10m, but can be changed in the request) and current date.

The following example illustrates how that works:

![Scheduling example](https://monosnap.com/image/6szBmxoUUUZwO7QT5kf5xVYteo9n3C)

In the above example, the schedule operation was executed at 22:55 and detected 2 operations to run at 22:52 (in the range of 10m) performing
the defined moderation status change.


## Usage

1. Add a new field to your collection of type Moderation.
You can name whatever you want for your moderation field, e.g. status, moderation, etc.. But you need to keep in mind
if you change the field later you may need to manually update all existing collection entries.
2. When creating a new collection entry the moderation value will be `Draft`, can be changed to `Unpublished` or `Published`.
3. When editing an existing collection the moderation value will change automatically to `Draft`.
4. When retrieving a collection entry (or list of entries) only entries with moderation value of `Published` or `Draft` (if there is a `Published` revision before the `Draft` one) will be returned.

### Options
The moderation field supports the following options:

* `autodraft` (default: _true_) If set to _false_, entries that are being edited wont be set to `Draft`

### Localization

The moderation fields supports localization as any other Cockpit field, just enable the localization option in the field configuration.
When editing an entry, the moderation status will be saved according to the selected option on each language.

If Scheduling is enabled and the moderation field is set to be localized, the scheduling will be defined for each language.

## Demo

[![Screencast](https://monosnap.com/image/o9F3WihH3NtOk1VfszARSa402sD12U)](http://www.youtube.com/watch?v=TdhoThghRRY "Screencast")

- [Demo v1.3](http://www.youtube.com/watch?v=TdhoThghRRY "Screencast v1.3")
- [Demo v1.0](https://youtu.be/LywGxJqUJkg "Screencast v1.0")


## Copyright and license

Copyright since 2018 pauloamgomes under the MIT license.
