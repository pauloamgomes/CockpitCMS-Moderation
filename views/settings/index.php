<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/settings')">@lang('Settings')</a></li>
        <li class="uk-active"><span>@lang('Moderation API Access')</span></li>
    </ul>
</div>

<div class="uk-margin-top uk-form" riot-view>

    <div class="uk-grid">
        <div class="uk-width-2-3">

            <div class="uk-text-large uk-text-bold">
                <span class="uk-text-uppercase">@lang('Moderation API-Key')</span>
                <span class="uk-badge uk-badge-danger" show="{ key }">@lang('Share with caution')</span>
            </div>

            <div class="uk-grid uk-grid-small uk-margin-top">
                <div class="uk-flex-item-1">
                    <input class="uk-width-1-1 uk-form-large uk-text-primary" type="text" placeholder="@lang('No key generated')" bind="key" name="fullaccesskey" readonly>
                    <div class="uk-text-small uk-text-muted">
                        @lang('Use the api token as an extra query parameter (previewToken) in your API requests')
                    </div>
                </div>
                <div if="{key}">
                    <button class="uk-button uk-button-link uk-button-large" type="button" onclick="{ copyApiKey }" title="@lang('Copy Token')" data-uk-tooltip="pos:'top'"><i class="uk-icon-copy"></i></button>
                    <button class="uk-button uk-button-link uk-button-large" type="button" onclick="{ removeKey }" title="@lang('Delete')" data-uk-tooltip="pos:'top'"><i class="uk-icon-trash-o uk-text-danger"></i></button>
                </div>
                <div>
                    <button class="uk-button uk-button-primary uk-button-large" type="button" onclick="{ generate }" title="@lang('Generate Token')" data-uk-tooltip="pos:'top'"><i class="uk-icon-magic"></i></button>
                </div>

            </div>

            <div class="uk-margin-large-top" show="{ key }">
                <button class="uk-button uk-button-primary uk-button-large" type="button" name="button" onclick="{ save }">@lang('Save')</button>
                <a class="uk-button uk-button-large uk-button-link" href="@route('/settings')">@lang('Close')</a>
            </div>

        </div>

        <div class="uk-width-1-3">
        </div>
    </div>


    <script type="view/script">

        this.mixin(RiotBindMixin);

        var $this = this;

        this.key = {{ json_encode($key) }};

        this.on('mount', function(){

            // bind clobal command + save
            Mousetrap.bindGlobal(['command+s', 'ctrl+s'], function(e) {
                e.preventDefault();
                $this.save();
                return false;
            });
        });

        removeKey() {
            this.key = '';
        }

        generate(e) {
          this.key = App.Utils.generateToken(120);
        }

        copyApiKey(e) {
            App.Utils.copyText(this.key, function() {
                App.ui.notify("Copied!", "success");
            });
        }

        save() {
            App.callmodule('moderation:saveSettings', {key: this.key}).then(function(data) {
                App.ui.notify("Key saved", "success");
            });
        }

    </script>

</div>
