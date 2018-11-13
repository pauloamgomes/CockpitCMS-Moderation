<style>
.uk-moderation-element .uk-badge {
  min-width: 90px;
  text-align: left;
  padding: 6px 8px;
}
.uk-moderation-Unpublished .uk-badge {
  background-color: #d85030;
  color: #ffffff;
}
.uk-moderation-Draft .uk-badge {
  background-color: #e28327;
  color: #ffffff !important;
}
.uk-moderation-Published .uk-badge {
  background-color: #659f13;
  color: #ffffff !important;
}
</style>

<div class="uk-margin moderation-status" if="{moderation && moderation.length}">
  <div class="uk-width-1-1 uk-form-select uk-moderation-element uk-moderation-{ entry.status }">
    <label class="uk-text-small">@lang('Moderation Status:')</label>
    <div class="uk-margin-small-top">
      <span class="uk-badge uk-badge-outline">
        <i if="{entry.status == 'Unpublished'}" class="uk-icon-circle-o"></i>
        <i if="{entry.status == 'Draft'}" class="uk-icon-pencil"></i>
        <i if="{entry.status == 'Published'}" class="uk-icon-circle"></i>
        @lang("{entry.status}")
      </span>
    </div>
    <select bind="entry.status">
      <option value="Unpublished">@lang('Unpublished')</option>
      <option value="Draft">@lang('Draft')</option>
      <option value="Published">@lang('Published')</option>
    </select>
  </div>
</div>

<script>
  var $this = this;

  this.on('mount', function(){
    $this.moderation = this.collection.fields.filter(function(field) {
      return field.name === 'status';
    });

    $this.entry.status = ($this.moderation.length && $this.entry.status) || 'Draft';

    window.setTimeout(function() {
      sidebar = document.querySelector('.uk-grid-margin.uk-flex-order-first');
      sidebar.insertBefore(document.querySelector('.moderation-status'), sidebar.childNodes[0]);
    }, 50);

    $this.update();

  });

</script>
