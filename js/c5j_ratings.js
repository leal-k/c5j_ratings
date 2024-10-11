function getRatings(getUrl, params) {
    updateRatings(getUrl, params);
}

function updateRatings(url, params) {
    $.ajax({
        url: url,
        type: 'post',
        data: params,
        success: function (data) {
            if (Array.isArray(params['cID'])) {
                data.forEach(function (item) {
                    updateRatingButtons(item);
                });
                return;
            }
            updateRatingButtons(data);
        },
        error: function (data) {
            console.log("Error: ", data);
        }
    });
}

function updateRatingButtons(data) {
    let ratingValueID = $("#rating-value-" + data['bID'] + "-" + data['cID']);
    let ratingBtnID = $("#rating-" + data['bID'] + "-" + data['cID']);
    let activeClass = ratingBtnID.data('btn-type') + '-active';
    if (ratingValueID) {
        ratingValueID.text(data['ratings']);
    }
    if(data['isRated']){
        ratingBtnID.addClass(activeClass);
    }else{
        ratingBtnID.removeClass(activeClass);
    }
}