App.Utils.renderer.moderation = function(v) {
    switch (v) {
        case "Published":
            icon = "uk-icon-circle";
            break;
        case "Unpublished":
            icon = "uk-icon-circle-o";
            break;
        case "Draft":
            icon = "uk-icon-pencil";
            break;
    }
    return '<span class="uk-moderation-list uk-moderation-' + v + '"><span class="uk-badge uk-badge-outline"><i class="' + icon + '"></i> ' + v + "</span></span>";
};
