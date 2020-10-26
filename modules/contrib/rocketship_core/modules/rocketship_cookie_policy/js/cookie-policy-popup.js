(function ($, Drupal, drupalSettings) {
    var cookie_consent = {
        cookie_name: null,
        template: null,
        getCookie: function (cookie_name) {
            return this.getItem(cookie_name);
        },
        setCookie: function (cookie_name) {
            this.setItem(cookie_name, true, Infinity, '/');
        },
        getItem: function (sKey) {
            if (!sKey) {
                return null;
            }
            return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
        },
        setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
            if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) {
                return false;
            }
            var sExpires = "";
            if (vEnd) {
                switch (vEnd.constructor) {
                    case Number:
                        sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
                        break;
                    case String:
                        sExpires = "; expires=" + vEnd;
                        break;
                    case Date:
                        sExpires = "; expires=" + vEnd.toUTCString();
                        break;
                }
            }
            document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
            return true;
        },
        build: function (template, cookie_name) {
            var _this = this;
            $('body').append(template);
            $('#cookie-consent-popup').find('a.dismiss').on('click', function (event) {
                // sticking it in a var breaks it, for some reason
                event.preventDefault();
                _this.setCookie(cookie_name);
                $('body').removeClass('cookiePolicy-is-visible');
                $('#cookie-consent-popup').remove();
            });
        },
        run: function (template, cookie_name) {
            setTimeout(function() { $('body').addClass('cookiePolicy-is-visible'); }, 2500);

            if (this.getCookie(cookie_name) !== null) {
                //cookie already set, abort
                return false;
            }
            this.build(template, cookie_name);
        }
    };

    cookie_consent.run(drupalSettings.rocketship_cookie_policy.template, 'cookie_consent');

})(jQuery, Drupal, drupalSettings);