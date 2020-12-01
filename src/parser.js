var products = {
    product: []
};
async function getSettings() {
    return new Promise((res, _) => {
        chrome.storage.sync.get('parser_settings', (data) =>
            res(data && data.parser_settings ? data.parser_settings : null)
        );
    });
}

async function getProduct(url) {
    var product = {};
    const attributes = {};
    const settings = await getSettings();
    console.log(settings);
    const pageDescription = await $.ajax({url: url});
    const tabs = $(pageDescription).find("div[data-cs-name='mc-tabs-usual']");
    const specUrl = $(tabs[1]).find("a").attr('href');
    const pageSpec = await $.ajax({url: specUrl});
    product.title = $(pageDescription).find("h1").text();
    product.lowPrice = $(pageDescription).find("meta[itemProp='lowPrice']").attr('content');
    product.highPrice = $(pageDescription).find("meta[itemProp='highPrice']").attr('content');
    let breadcrumb = $(pageDescription).find("div[data-zone-name='breadcrumb'] span");
    product.brand = $(breadcrumb[breadcrumb.length - 1]).text();
    if (settings.category) {
        product.productCategory = [settings.category];
    }
    $(pageDescription).find("h3").each(function (i, el) {
        if ($(el).text() === 'Коротко о товаре') {
            product.description = $(el).next('ul').html();
        }
    });
    const imagesFind = [...pageDescription.matchAll(/<meta property="vk:image" content="(.*?)"/g)];
    product.images = [...imagesFind.map(function (el) {
        return el[1];
    })];
    const attributeGroupeMain = $(pageSpec).find("h2");
    let attrMain = attributeGroupeMain.each(function (i, el) {
        if ($(el).text() === "Подробные характеристики") {
            return el;
        }
    });
    let attrDiv = attrMain.parent().find('h2');
    var attributeGroupe = {};
    attrDiv.each(function (i, el) {
        var attributeGroupeValue = $(el).text();
        if (attributeGroupeValue === 'Подробные характеристики') {
            return;
        }
        var tmpAttributes = {};
        let blocks = $(el).parent().find('dl');
        blocks.each(function (i, el) {
            var attributes = {};
            attributes.description = $(el).find('dt').text();
            attributes.value = $(el).find('dd').text();
            tmpAttributes[i] = attributes;
        });
        if (!$.isEmptyObject(tmpAttributes)) {
            attributeGroupe[attributeGroupeValue] = tmpAttributes;
        }
    });
    product.attributes = attributeGroupe;
    return product;
}

function addProducts(items, data) {
    if (data.apiurl === null || data.apitoken === null || data.apitoken === '') {
        console.log('Проверьте настройки');
        return false;
    }
    $.ajax({
        url: data.apiurl + "/index.php?route=api/products/addProducts&api_token=" + data.apitoken,
        type: "post",
        data: {
            "products": JSON.stringify(items)
        },
        complete: function (jqXHR, textStatus) {
            if (textStatus == 'success') {
                // alert('Успешно.');
            }
            if (textStatus == 'error') {
                // alert('Ошибка.');
            }

        },
        success: function (data) {
            console.log("%c" + data.success, "color:#000;background:#fff1f3;padding:1px;border-radius:1px");
            products.product.push(items);
            console.log(products);
        }
    });
}


$(document).ready(function () {

    $("article").append("<div class='tooltip right_tooltip'><button class='testButton'><img src='" + chrome.extension.getURL('/images/download_folder.svg') + "'></button></div>");

  //  $("h1").before("<button class='testButton1'>Test1</button>");
    $("h1").before("<button class='testButton2'>Парсить страницу</button>");

    $(".testButton2").on("click", async function () {
        let linkProduct = [];
        const baseUrl = window.location.origin;
        console.log('start');

        let article = $(window.document).find('article');
        article.each(function (i, el) {
            var hr = $(el).find('h3[data-zone-name="title"]').children("a").attr('href');
            linkProduct.push(baseUrl + hr);
        });

        for (j = 0; j < 15; j++) {
            const product = await getProduct(linkProduct[j]);
            const settings = await getSettings();
            addProducts(product, settings);
        }

    });

    $(".testButton").on("click", async function () {
        let baseUrl = window.location.origin;
        let article = $(this).parents('article');
        let linkProduct = article.find('h3[data-zone-name="title"]').children("a").attr('href');
        console.log(linkProduct);
        hr = baseUrl + linkProduct;
        const product = await getProduct(hr);
        const settings = await getSettings();
        addProducts(product, settings);
    });


});

function getElementByXpath(path) {
    return document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
}

function updatePrice(items, data) {
    if (data.apiurl === null || data.apitoken === null || data.apitoken === '') {
        console.log('Проверьте настройки');
        return false;
    }
    $.ajax({
        url: data.apiurl + "/index.php?route=api/products/index&api_token=" + data.apitoken,
        type: "post",
        data: {
            "product_id": "30",
            "new_price": items.low_price,
        },
        complete: function (jqXHR, textStatus) {
            if (textStatus == 'success') {
                // alert('Успешно.');
            }
            if (textStatus == 'error') {
                // alert('Ошибка.');
            }

        },
        success: function (data) {
            console.log(data);
        }
    });
}

function getSpec(hr, data = '') {
    console.log('ajax2');
    let baseUrl = window.location.origin;
    let attributes = {};
    $.ajax({
        url: baseUrl + hr,
        success: function (data) {
            const attributeGroupeMain = $(data).find("h2");
            let attrMain = attributeGroupeMain.each(function (i, el) {
                if ($(el).text() === "Подробные характеристики") {
                    return el;
                }
            });
            let attrDiv = attrMain.parent().find('h2');
            var attributeGroupe = {};
            attrDiv.each(function (i, el) {
                var attributeGroupeValue = $(el).text();
                if (attributeGroupeValue === 'Подробные характеристики') {
                    return;
                }
                var tmpAttributes = {};
                let blocks = $(el).parent().find('dl');
                blocks.each(function (i, el) {
                    var attributes = {};
                    attributes.description = $(el).find('dt').text();
                    attributes.value = $(el).find('dd').text();
                    tmpAttributes[i] = attributes;

                });
                if (!$.isEmptyObject(tmpAttributes)) {
                    attributeGroupe[attributeGroupeValue] = tmpAttributes;
                }
            });
            let items = {};
            items.attributes = attributeGroupe;
            attributes.attributes = items.attributes;
            product.attributes = items.attributes;
        }
    });
}


	

	

