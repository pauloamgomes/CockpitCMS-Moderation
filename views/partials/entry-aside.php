<div class="uk-margin moderation-status" if="{field && field.length && moderation_field}">
  <div class="uk-width-1-1 uk-form-select uk-moderation-element uk-moderation-{ entry[moderation_field] }">
    <label class="uk-text">
      <i if="{originalModeration == 'Unpublished'}" class="icon-Unpublished uk-icon-circle-o"></i>
      <i if="{originalModeration == 'Draft'}" class="icon-Draft uk-icon-pencil"></i>
      <i if="{originalModeration == 'Published'}" class="icon-Published uk-icon-circle"></i>
      <strong>@lang('Status:')</strong> {originalModeration}
    </label>
    <div class="uk-margin-small-top">
      <span class="uk-badge uk-badge-outline">
        @lang("Change to:") <strong>@lang("{entry[moderation_field]}")</strong>
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
  $this.originalModeration = '';
  $this.canPublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'publish']) ? 1 : 0 }};
  $this.canUnpublish = {{ $app->module("cockpit")->hasaccess('moderation', ['manage', 'unpublish']) ? 1 : 0 }};

  this.on('mount', function() {

    $this.field = this.collection.fields.filter(function(definition) {
      return definition.type === 'moderation';
    });

    if (!$this.field.length || $this.field[0].name === undefined) {
      return;
    }

    $this.moderation_field = $this.field[0].name;

    $this.originalModeration = $this.entry[$this.moderation_field] || 'Draft';
    $this.entry[$this.moderation_field] = 'Draft';

    window.setTimeout(function() {
      sidebar = document.querySelector('.uk-width-medium-1-4.uk-flex-order-first');
      sidebar.insertBefore(document.querySelector('.moderation-status'), sidebar.childNodes[0]);
    }, 50);

    $this.update();
  });

</script>
