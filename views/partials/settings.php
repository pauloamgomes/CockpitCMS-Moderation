<?php

/**
 * @file
 * Moderation settings entry view.
 */
?>

<div>
    <div class="uk-panel uk-panel-space uk-panel-box uk-panel-card">
        <img src="@url('assets:app/media/icons/lock.svg')" width="50" height="50" alt="@lang('Moderation')" />
        <div class="uk-text-truncate uk-margin">
            @lang('Moderation')
        </div>
        <a class="uk-position-cover" href="@route('/settings/moderation')"></a>
    </div>
</div>
