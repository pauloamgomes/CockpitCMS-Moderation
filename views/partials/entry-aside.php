<div ref="scheduleModal" class="uk-modal uk-modal-schedule">
  <div class="uk-modal-dialog uk-form">
      <h3 class="uk-text-bold uk-flex uk-flex-middle">@lang('Set Schedule')</h3>
      <div class="uk-margin-top uk-overflow-container uk-panel1">
          <div class="uk-text-muted">
            <span>@lang('Configure the scheduling moderation status and the date/time.')</span>
          </div>
          <div class="schedule-container">
              <div class="uk-margin-top" if="{ localize && lang }">
                  <div class="uk-flex-item-1">
                    <label class="uk-text-small">@lang('Language:')</label><br />
                    <span>{ getLangLabel(lang) }</span>
                  </div>
              </div>
              <div class="uk-margin-top">
                  <div class="uk-flex-item-1">
                    <div class="uk-form-select" data-uk-form-select>
                        <label class="uk-text-small">@lang('Moderation Status:')</label>
                        <input placeholder="select..." class="uk-width-1-1 uk-form-blank" value="{ schedule && schedule.type }">
                        <select bind="schedule.type">
                          <option value="">Select</option>
                          <option if="{ canPublish }" value="Publish">@lang("Publish")</option>
                          <option if="{ canUnpublish }" value="Unpublish">@lang("Unpublish")</option>
                        </select>
                      </div>
                  </div>
              </div>
              <div class="uk-margin-top uk-grid">
                  <div class="uk-width-1-2">
                    <label class="uk-text-small">@lang('Select a future Date:')</label>
                    <field-date bind="schedule.date" placeholder="select date..."></field-date>
                  </div>
                  <div class="uk-width-1-2 schedule-time">
                    <label class="uk-text-small">@lang('Select Time:')</label><br />
                    <field-time bind="schedule.time"></field-time>
                  </div>
              </div>
          </div>
      </div>
      <div class="uk-modal-footer uk-text-right">
          <button if="{ scheduleIsValid() }" class="uk-button uk-button-link uk-button-large" onclick="{ setSchedule }">
            @lang('Save')
          </button>
          <button if="{ !scheduleIsValid() }" class="uk-button uk-button-link uk-button-large" disabled>
            @lang('Save')
          </button>
          <button class="uk-button uk-button-link uk-button-large uk-modal-close" onclick="{ closeSchedule }">
            @lang('Cancel')
          </button>
      </div>
  </div>
</div>

<div class="uk-margin moderation-status { canSchedule ? 'with-schedule' : '' }" if="{field && field.length && moderation_field}">
  <label class="uk-text-small">@lang('Moderation')</label>
  <div class="uk-width-1-1 uk-form-select uk-moderation-element uk-moderation-{ entry[moderation_field] }">
    <label class="uk-text">
      <i if="{originalModeration[lang] == 'Unpublished'}" class="icon-Unpublished uk-icon-circle-o"></i>
      <i if="{originalModeration[lang] == 'Draft'}" class="icon-Draft uk-icon-pencil"></i>
      <i if="{originalModeration[lang] == 'Published'}" class="icon-Published uk-icon-circle"></i>
      <strong>@lang('Status:')</strong> {originalModeration[lang]}
    </label>
    <div class="uk-margin-small-top">
      <span class="uk-badge uk-badge-outline">
        {originalModeration[lang] !== entry[moderation_field] ? App.i18n.get("Change to:") : App.i18n.get("Save as:")} <strong>@lang("{entry[moderation_field]}")</strong>
      </span>
    </div>
    <select bind="entry.{moderation_field}">
      <option if="{ canUnpublish }" selected="{ entry[moderation_field] === 'Unpublished' }" value="Unpublished">@lang("Unpublished")</option>
      <option selected="{ entry[moderation_field] === 'Draft' }" value="Draft">@lang("Draft")</option>
      <option if="{ canPublish }" selected="{ entry[moderation_field] === 'Published' }" value="Published">@lang("Published")</option>
    </select>
  </div>
  <div if="{ canSchedule && entry._id }" class="uk-margin-small-top">
    <label if="{ scheduleIsValid() }" class="uk-text-small">@lang('Schedule')</label>
    <div if="{ !scheduleIsValid() }" class="uk-text-small uk-text-muted">
      <a class="uk-text-medium" onClick="{ showSchedule }"><i class="uk-icon-clock-o"></i> @lang('Schedule')</a>
    </div>
    <div if="{ scheduleIsValid() }" class="uk-text-medium uk-text-muted">
      <strong><i class="uk-icon-clock-o"></i> { schedule.type }</strong><br />
      <span>{ schedule.date } { schedule.time }</span> <a class="uk-text-small uk-text-danger" onClick="{ cancelSchedulePrompt }"><i class="uk-icon-trash"></i> @lang('Cancel')</a>
    </div>
  </div>
</div>

<script>
  var $this = this;
  $this.scheduleModal = null;
  $this.moderation_field = 'status';
  $this.originalModeration = {'': 'Draft'};
  $this.canPublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'publish']) ? 1 : 0 }};
  $this.canUnpublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'unpublish']) ? 1 : 0 }};
  $this.lang = $this.lang || "";
  $this.localize = false;
  $this.canSchedule = {{ json_encode($enabled) }};
  $this.schedule = false;
  $this.langLabel = null;

  var oldXHROpen = window.XMLHttpRequest.prototype.open;
  window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
   if (/^.*(\/collections\/save_entry\/\w+)$/.test(url)) {
     this.addEventListener('load', function() {
       var entry = JSON.parse(this.responseText);

       $this.originalModeration[''] = entry[$this.moderation_field_name];
       if ($this.localize) {
         for (var l of $this.languages) {
           $this.originalModeration[l.code] = entry[$this.moderation_field_name + "_" + l.code];
         }
       }
     });
   }
   return oldXHROpen.apply(this, arguments);
  };

  this.on('mount', function() {
    $this.field = this.collection.fields.filter(function(definition) {
      return definition.type === 'moderation';
    });

    if (!$this.field.length || $this.field[0].name === undefined) {
      return;
    }

    $this.localize = $this.field[0].localize || false;
    $this.moderation_field_name = $this.field[0].name;
    $this.moderation_field = moderation_field();

    $this.originalModeration[''] = $this.entry[$this.moderation_field_name] || 'Draft';
    if ($this.entry[$this.moderation_field_name] !== 'Unpublished') {
        $this.entry[$this.moderation_field_name] =  'Draft';
    }
    if ($this.localize) {
      for (var l of $this.languages) {
        $this.originalModeration[l.code] = $this.entry[$this.moderation_field_name + "_" + l.code] || 'Draft';
        if ($this.entry[$this.moderation_field_name + "_" + l.code] !== 'Unpublished') {
          $this.entry[$this.moderation_field_name + "_" + l.code] = 'Draft';
        }
      }
    }

    window.setTimeout(function() {
      sidebar = document.querySelector('.uk-width-medium-1-4.uk-flex-order-first');
      sidebar.insertBefore(document.querySelector('.moderation-status'), sidebar.childNodes[0]);
    }, 50);

    if (this.canSchedule && this.entry._id) {
      this.getSchedule();
    }

    this.scheduleModal = UIkit.modal(this.refs.scheduleModal, { modal: false, 'escclose': false, 'bgclose': false, 'keyboard': false });
    this.scheduleModal.on('hide.uk.modal', this.hideSchedule);

    $this.update();
  });

  this.on("update", function() {
    $this.moderation_field = moderation_field();
  });

  this.on('bindingupdated', function(data) {
    if (this.entry._id && this.canSchedule && data[0] && data[0] === 'lang') {
      this.getSchedule();
    }
  });

  function moderation_field() {
    return $this.localize && $this.lang
      ? $this.moderation_field_name + "_" + $this.lang
      : $this.moderation_field_name;
  }

  this.getSchedule = function() {
    var filter = {
      id: this.entry._id,
      lang: (this.localize && this.lang) || ""
    };
    App.callmodule('moderation:getSchedule', filter, 'schedule').then(function(data) {
      $this.schedule = (data.result && data.result.schedule) || null;
      $this.update();
    }).catch(function(e){
      App.ui.notify('Error loading schedule information!', 'danger');
    });
  }

  this.setSchedule = function() {
    if (!this.scheduleDateIsValid()) {
      App.ui.notify('Invalid schedule date/time, must be a valid future date', 'danger');
      return;
    }
    var schedule = {
      id: this.entry._id,
      schedule: this.schedule,
      field: this.moderation_field_name,
      collection: this.collection.name,
      lang: (this.localize && this.lang) || ""
    };
    App.callmodule('moderation:setSchedule',  schedule, 'schedule').then(function(data) {
      App.ui.notify('Schedule: entry will be <strong>' + $this.schedule.type + "</strong> on <strong>" + $this.schedule.date + " " + $this.schedule.time + '</strong>', 'success');
    }).catch(function(e){
      App.ui.notify('Error creating schedule!', 'danger');
    });
    $this.scheduleModal.hide();
    $this.update();
  }

  this.hideSchedule = function(e) {
    if (!$this.scheduleIsValid()) {
      $this.schedule = null;
      $this.update();
    }
  }

  this.closeSchedule = function(e) {
    $this.schedule = null;
    $this.update();
  }

  this.showSchedule = function() {
    $this.scheduleModal.show();
  }

  this.cancelSchedulePrompt = function() {
    App.ui.confirm("Are you sure?", function() {
      App.callmodule('moderation:removeSchedule', { id: $this.entry._id, lang: $this.lang || "" }, 'schedule').then(function(data) {
        $this.schedule = null;
        App.ui.notify('Schedule removed!', 'success');
        $this.update();
      }).catch(function(e) {
        App.ui.notify('Error removing schedule!', 'danger');
      });
    });
  }

  this.scheduleIsValid = function() {
    return this.schedule && this.schedule.type && $this.schedule.date && $this.schedule.time;
  }

  this.scheduleDateIsValid = function() {
    var sdate = moment($this.schedule.date + " " + $this.schedule.time);
    return sdate.isAfter(moment(), 'minute');
  }

  this.getLangLabel = function(code) {
    for (key in this.languages) {
      if (this.languages[key].code === code) {
        return App.i18n.get(this.languages[key].label);
      }
    }
  }


</script>
