function getRatings(getUrl, params) {
    updateRatings(getUrl, params);
}

function updateRatings(url, params, multi = false) {
    $.ajax({
        url: url,
        type: 'post',
        data: params,
        success: function(data) {
            if (multi) {
                data.forEach(function (item) {
                    updateRatingButtons(item);
                });
                return;
            }
            updateRatingButtons(data);
        }
    });
}

function updateRatingButtons(data) {
    $('.rating-'+data['cID']).each(function () {
        let activeClass = $(this).data('btn-type') + '-active';
        $(this).toggleClass(activeClass, data['isRated']);
        if ($(this).next().is('span')) {
            $(this).next().text(data['ratings']);
        }
    });
}