var readyNeosRulezShopDocument = (callback) => {
    if (document.readyState != "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
}

function cartCount() {
    if(document.getElementById('cartcount')) {
        fetch('/countcart')
            .then(response => response.json())
            .then(data => data > 0 ? document.getElementById('cartcount').innerText = data : '');
    }
}

readyNeosRulezShopDocument(() => {
    if(document.getElementById('product_form')) {
        document.getElementById('product_form').addEventListener('submit', (e) => {
            e.preventDefault();
            document.querySelector('body').classList.add('body-hide');
            let postUrl = document.getElementById('product_form').action;
            let formData = new FormData();
            document.querySelectorAll('#product_form input, #product_form select').forEach(inputField => {
                formData.append(inputField.name, inputField.value);
            });
            var request = new XMLHttpRequest();
            request.open('POST', postUrl);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState === 4) {
                    if(request.status == 200) {
                        let parser = new DOMParser();
                        let doc = parser.parseFromString(request.responseText, 'text/html');
                        let refreshedProductForm = doc.getElementById('product_form');
                        let productForm = document.getElementById('product_form');
                        if(refreshedProductForm) {
                            productForm.innerHTML = refreshedProductForm.innerHTML;
                        } else {
                            productForm.remove();
                        }
                        cartCount();
                    }

                }
            }
            if(document.querySelector('.cart-alert')) {
                $('.cart-alert').slideDown();
            }
            document.querySelector('body').classList.remove('body-hide');
        });
    }
    cartCount();
});
