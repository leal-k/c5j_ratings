function getRatings(getUrl, params) {
    updateRatings(getUrl, params);
}

function updateRatings(url, params) {
    $.ajax({
        url: url,
        type: 'post',
        data: params,
        success: function(data) {
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