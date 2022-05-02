//Pagination through jquery load method 
$('body').on('click', '.pagination a', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    $('.content').load(url);
    return false;
});
//Pagination through jquery load method 
$('body').on('click', '.filterpagination .pagination a', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    var searchtext = $(".searchtext").val();
    var categoryid = $("#service-category-id").val();
    var subcategoryid = $("#service-subcategory-id").val();
    var deliverydays = $("#delivery_days").val();
    var sellerlanguages = $("#seller-languages").val();
    var pricerange = $("#price").val();
    $('.content').load(url,{"_token": _token,'searchtext': searchtext, 'categoryid': categoryid, 'subcategoryid': subcategoryid, 'deliverydays': deliverydays, 'sellerlanguages': sellerlanguages,'pricerange':pricerange});
    return false;
});