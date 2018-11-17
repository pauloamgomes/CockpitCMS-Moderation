<field-moderation class="field-moderation-field">
  <select ref="input" bind="{ opts.bind }">
    <option selected="{ parent.root.$value === opts.bind.value }" value="Unpublished">Unpublished</option>
    <option selected="{ parent.root.$value === opts.bind.value }" value="Draft">Draft</option>
    <option selected="{ parent.root.$value === opts.bind.value }" value="Published">Published</option>
  </select>
  <script>

    var $this = this;

    this.on('mount', function() {
      if (document.querySelector('.uk-moderation-element')) {
        this.parent.root.closest(".uk-width-medium-1-1").style.display = "none";
      }
      this.update();
    });

    this.$updateValue = function(value) {
      this.update();
    }.bind(this);

  </script>
</field-moderation>
