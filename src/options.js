/*
https://oc3036.oc-parser.ru
testapi
0RaJ503nIm7EOhJN0MPZ5fx7OsILNl7Iy1yGZ3MJQLEupDHY6BRnLMVj7zib7bhBDJtY14UGK7Bw0rYOpKCGD1NIqdBURurbTI9qcHxRjVog1mK8j0IQs2k5Mdh0Du1mp9VyhpTnhn00PBi8290l0ccsR5kHF3ax4PFDaCm9Fim5IbEtd0TBgF4z1lAXrYEGjN5Dlc2JNmyh4Q3LzxFE9QoBLPy9DO14avGs3Ptr3WO2SfBCDeEekianUbAU4Eet

*/

//content.js

$(document).ready(function () {
    $('#extentionID').val(chrome.runtime.id);
});

(async function () {
    const $banBtn = document.querySelector('#getToken');
    const $saveSetting = document.querySelector('#saveSetting');
    const $getSettings = document.querySelector('#getSettings');
    const $getCatetegory = document.querySelector('#getCatetegory');
    $banBtn.addEventListener('click', () => {
        let error = false;
        let $settings = document.querySelectorAll("input[name*='settings']");
        let settings = [...$settings].reduce((obj, el) => {
            if (
                el.value === '' &&
                (el.id === 'apiurl' || el.id === 'apiusername' || el.id === 'apikey')
            ) {
                alert('Значение ' + el.previousElementSibling.innerHTML + ' не может быть пустым!');
                error = true;
            } else {
                obj[el.id] = el.value;
            }
            return obj;
        }, {});

        if (!error) {
            $.ajax({
                url: settings.apiurl + '/index.php?route=api/products/login',
                type: 'POST',
                data: {
                    username: settings.apiusername,
                    key: settings.apikey,
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
                    parserLog('apitoken: ' + data.api_token);
                    apitoken.value = data.api_token;
                },
            });
        }
    });

    $saveSetting.addEventListener('click', () => {
        let $settings = document.querySelectorAll("[name*='settings']");
        const obj = {};
        obj.settings = checkValue($settings);
        if (obj && obj.settings) {
            chrome.storage.sync.set({ parser_settings: obj.settings }, function (item) {
                console.log('Saved');
            });
        } else {
            console.group('Parser');
            console.error('Ошибка сохранения настроек!');
            console.groupEnd();
        }
    });

    $getSettings.addEventListener('click', () => {
        chrome.storage.sync.get('parser_settings', function (items) {
            console.log(items);
            if (!chrome.runtime.error) {
                for (let key in items.settings) {
                    console.log(key + ' : ' + items.settings[key]);
                }
            }
        });
    });

    const settings = await getSettings();

    $.ajax({
        url:
            settings.apiurl +
            '/index.php?route=api/products/getCategories&api_token=' +
            settings.apitoken,
        type: 'post',
        data: {
            product_id: '30',
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
            if (data.categories) {
                let $category = $('#category');
                let $categories = $('#categories');
                $categories.val(JSON.stringify(data.categories));
                $category.html('');
                $category.append("<option value='0'></option>");
                for (let i = 0; i < data.categories.length; i++)
                    $category.append(
                        "<option value='" +
                            data.categories[i].category_id +
                            "'>" +
                            data.categories[i].name +
                            '</option> '
                    );
                if (settings.category != 0) {
                    $('#category option[value=' + settings.category + ']').prop('selected', true);
                }
            } else {
                console.log('Нет списка категорий!');
            }
        },
    });

    chrome.storage.sync.get('parser_settings', function (items) {
        if (!chrome.runtime.error) {
            let $settings = document.querySelectorAll("[name*='settings']");
            console.log($settings);
            [...$settings].reduce((obj, el) => {
                if (el.tagName === 'SELECT') {
                    //el.ch
                } else {
                    el.value = items.parser_settings[el.id] ? items.parser_settings[el.id] : '';
                }
            }, {});
        }
    });
})();

async function getSettings() {
    return new Promise((res, _) => {
        chrome.storage.sync.get('parser_settings', (data) =>
            res(data && data.parser_settings ? data.parser_settings : null)
        );
    });
}

function checkValue($settings) {
    let settings = {};
    let error = false;
    settings = [...$settings].reduce((obj, el) => {
        if (el.required) {
            if (el.value != '') {
                obj[el.id] = el.value;
                return obj;
            } else {
                alert('Значение ' + el.previousElementSibling.innerHTML + ' не может быть пустым!');
                error = true;
            }
        } else {
            obj[el.id] = el.value;
            return obj;
        }
    }, {});

    if (!error) return settings;
}

function parserLog(msg, type = 'log') {
    console.group('Parser');
    if (type === 'log') {
        console.log(msg);
    }

    if (type === 'error') {
        console.error(msg);
    }
    console.groupEnd();
}
