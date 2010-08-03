function sort(list, key) {
    $($(list).get().reverse()).each(function(outer) {
        var sorting = this;
        $($(list).get().reverse()).each(function(inner) {
            if($(key, this).text().localeCompare($(key, sorting).text()) > 0) {
                this.parentNode.insertBefore(sorting.parentNode.removeChild(sorting), this);
            }
        });
    });
}

