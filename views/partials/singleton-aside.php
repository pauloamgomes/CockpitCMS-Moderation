<div class="uk-margin moderation-status" if="{field && field.length && moderation_field}">
  <label class="uk-text-small">@lang('Moderation')</label>
  <div class="uk-width-1-1 uk-form-select uk-moderation-element uk-moderation-{ data[moderation_field] }">
    <label class="uk-text">
      <i if="{originalModeration[lang] == 'Unpublished'}" class="icon-Unpublished uk-icon-circle-o"></i>
      <i if="{originalModeration[lang] == 'Draft'}" class="icon-Draft uk-icon-pencil"></i>
      <i if="{originalModeration[lang] == 'Published'}" class="icon-Published uk-icon-circle"></i>
      <strong>@lang('Status:')</strong> {App.i18n.get(originalModeration[""])}
    </label>
    <div class="uk-margin-small-top">
      <span class="uk-badge uk-badge-outline">
        {originalModeration[lang] !== data[moderation_field] ? App.i18n.get("Change to:") : App.i18n.get("Save as:")} <strong>{App.i18n.get(data[moderation_field])}</strong>
      </span>
    </div>
    <select bind="data.{moderation_field}">
      <option if="{ canUnpublish }" selected="{ data[moderation_field] === 'Unpublished' }" value="Unpublished">@lang("Unpublished")</option>
      <option selected="{ data[moderation_field] === 'Draft' }" value="Draft">@lang("Draft")</option>
      <option if="{ canPublish }" selected="{ data[moderation_field] === 'Published' }" value="Published">@lang("Published")</option>
    </select>
  </div>

  <button onclick="{ saveAndPublish }" id="save-and-publish" class="uk-button uk-button-large uk-button-success" style="display: none;">@lang('Publish & Save')</button>
</div>

<script>
  var $this = this;
  $this.moderation_field = 'status';
  $this.originalModeration = {'': 'Draft'};
  $this.canPublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'publish']) ? 1 : 0 }};
  $this.canUnpublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'unpublish']) ? 1 : 0 }};
  $this.lang = $this.lang || "";
  $this.localize = false;
  $this.langLabel = null;

  var oldXHROpen = window.XMLHttpRequest.prototype.open;
  window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
   if (/^.*(\/singletons\/update_data\/\w+)$/.test(url)) {
     this.addEventListener('load', function() {
       var entry = JSON.parse(this.responseText);

       $this.originalModeration[''] = entry.data[$this.moderation_field_name];
       if ($this.localize) {
         for (var l of $this.languages) {
           $this.originalModeration[l.code] = entry.data[$this.moderation_field_name + "_" + l.code];
         }
       }
     });
   }
   return oldXHROpen.apply(this, arguments);
  };

  this.on('mount', function() {
    $this.field = this.fields.filter(function(definition) {
      return definition.type === 'moderation';
    });

    // Avoid triggering a save on enter keypress
    App.$('cp-field input').on('keypress', function(e) {
      if (e.keyCode === 13) {
        e.preventDefault();
      }
    });

    if (!$this.field.length || $this.field[0].name === undefined) {
      return;
    }

    $this.localize = $this.field[0].localize || false;
    $this.moderation_field_name = $this.field[0].name;
    $this.moderation_field = getModerationField();

    $this.originalModeration[''] = $this.data[$this.moderation_field_name] || 'Draft';
    if ($this.data[$this.moderation_field_name] !== 'Unpublished') {
        $this.data[$this.moderation_field_name] =  'Draft';
    }
    if ($this.localize) {
      for (var l of $this.languages) {
        $this.originalModeration[l.code] = $this.data[$this.moderation_field_name + "_" + l.code] || 'Draft';
        if ($this.data[$this.moderation_field_name + "_" + l.code] !== 'Unpublished') {
          $this.data[$this.moderation_field_name + "_" + l.code] = 'Draft';
        }
      }
    }

    window.setTimeout(function() {
      sidebar = document.querySelector('.uk-width-medium-1-4.uk-flex-order-first');
      sidebar.insertBefore(document.querySelector('.moderation-status'), sidebar.childNodes[0]);
      App.$('cp-actionbar .uk-container button.uk-button-primary').attr('id', 'save-entry-button');
      App.$('cp-actionbar .uk-container').prepend(App.$('#save-and-publish'));
      updateActions($this.data[$this.moderation_field]);
    }, 50);

    $this.update();
  });

  this.on("update", function() {
    $this.moderation_field = getModerationField();
  });

  this.on('bindingupdated', function(data) {
    if (this.singleton._id && data[0] && data[1] && data[0] === 'data.' + $this.moderation_field) {
      updateActions(data[1]);
    }
  });

  function getModerationField() {
    return $this.localize && $this.lang
      ? $this.moderation_field_name + "_" + $this.lang
      : $this.moderation_field_name;
  }

  function updateActions(status) {
    App.ui.notify(App.i18n.get('Singleton moderation status changed to') + ' <strong>' + status + '</strong>', 'success');
    if (status === 'Draft') {
      App.$('#save-and-publish').show();
      App.$('#save-entry-button').removeClass('uk-button-danger uk-button-success').addClass('uk-button-primary').html(App.i18n.get('Save Draft')).show();
    } else if (status === 'Published') {
      App.$('#save-and-publish').hide();
      App.$('#save-entry-button').removeClass('uk-button-primary uk-button-danger').addClass('uk-button-success').html(App.i18n.get('Save Published')).show();
    } else if (status === 'Unpublished') {
      App.$('#save-and-publish').hide();
      App.$('#save-entry-button').removeClass('uk-button-primary uk-button-success').addClass('uk-button-danger').html(App.i18n.get('Save Unpublished')).show();
    }
  }

  this.getLangLabel = function(code) {
    for (key in this.languages) {
      if (this.languages[key].code === code) {
        return App.i18n.get(this.languages[key].label);
      }
    }
  }

  this.saveAndPublish = function(e) {
    this.data[$this.moderation_field] = 'Published';
    $this.update();
    this.submit(e);
    updateActions(this.data[$this.moderation_field]);
    return false;
  }

</script>
