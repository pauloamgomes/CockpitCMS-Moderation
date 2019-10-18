<div class="uk-margin moderation-status" if="{field && field.length && moderation_field}">
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
</div>

<script>
  var $this = this;
  $this.moderation_field = 'status';
  $this.originalModeration = {'': 'Draft'};
  $this.canPublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'publish']) ? 1 : 0 }};
  $this.canUnpublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'unpublish']) ? 1 : 0 }};
  $this.lang = $this.lang || "";
  $this.localize = false;

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

    $this.update();
  });

  this.on("update", function() {
    $this.moderation_field = moderation_field();
  });

  function moderation_field() {
    return $this.localize && $this.lang
      ? $this.moderation_field_name + "_" + $this.lang
      : $this.moderation_field_name;
  }

</script>
