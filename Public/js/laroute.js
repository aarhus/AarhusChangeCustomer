;(function () {
    var module_routes = [
    {
        uri: "aarhuschangecustomer_unsub",
        name: "aarhuschangecustomer_index",
    },
    ]

    if (typeof laroute != "undefined") {
        laroute.add_routes(module_routes)
    } else {
        contole.log("laroute not initialized, can not add module routes:")
        contole.log(module_routes)
    }
})()
