import 'select2/dist/css/select2.min.css';
//import '../sass/3rd/_awesome-font.scss';

jQuery(function($) {
    $('.addon-color-field').wpColorPicker();

    //---------------------------------------------------------------
    // codemirror
    //---------------------------------------------------------------

    if (typeof codemirror_settings !== 'undefined') {
        const codemirror_css = document.querySelectorAll('.codemirror_css');
        const codemirror_html = document.querySelectorAll('.codemirror_html');

        function initializeCodeMirror(elements, settings, type) {
            elements.forEach(el => {
                if (!el.CodeMirror) {
                    let editorSettings = settings ? { ...settings } : {};
                    editorSettings.codemirror = {
                        ...editorSettings.codemirror,
                        indentUnit: 3,
                        tabSize: 3,
                        autoRefresh: true,
                    };
                    el.CodeMirror = wp.codeEditor.initialize(el, editorSettings);
                }
            });
        }

        initializeCodeMirror(codemirror_css, codemirror_settings.codemirror_css, 'CSS');
        initializeCodeMirror(codemirror_html, codemirror_settings.codemirror_html, 'HTML');
    }

    //---------------------------------------------------------------
    // Other
    //---------------------------------------------------------------

    $.fn.fadeOutAndRemove = function(speed) {
        return this.fadeOut(speed, function() {
            $(this).remove();
        });
    };

    $.fn.serializeObject = function() {
        let obj = {};
        let array = this.serializeArray();
        $.each(array, function() {
            let name = this.name;
            let value = this.value || '';
            if (name.indexOf('[]') > -1) {
                name = name.replace('[]', '');
                if (!obj[name]) {
                    obj[name] = [];
                }
                obj[name].push(value);
            } else {
                if (obj[name] !== undefined) {
                    if (!Array.isArray(obj[name])) {
                        obj[name] = [ obj[name] ];
                    }
                    obj[name].push(value);
                } else {
                    obj[name] = value;
                }
            }
        });

        return obj;
    };

    //---------------------------------------------------------------
    // ajax submit settings
    //---------------------------------------------------------------

    $(document).on('submit', '#_settings_form', function(e) {
        e.preventDefault();
        let $this = $(this);
        let $data = $this.serializeObject();

        let btn_submit = $this.find('button[name="_submit_settings"]');
        let button_text = btn_submit.html();
        let button_text_loading = '<span class="ajax-loader">&nbsp;</span>';

        btn_submit.prop('disabled', true).html(button_text_loading);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'submit_settings',
                _data: $data,
                _ajax_nonce: $this.find('input[name="_wpnonce"]').val(),
                _wp_http_referer: $this.find('input[name="_wp_http_referer"]').val(),
            },
        })
        .done(function(data) {
            $this.find('#_content').prepend(data);

            // auto reload tabs
            if (!window.location.hash ||
                window.location.hash === '#global_setting_settings' ||
                window.location.hash === '#custom_css_settings' ||
                window.location.hash === '#custom_script_settings' ||
                (window.location.hash === '#custom_sorting_settings' && $data['order_reset'] !== undefined) ||
                (window.location.hash === '#base_slug_settings' && $data['base_slug_reset'] !== undefined)
            ) {
                window.location.reload();
            }

            // dismissible auto hide
            setTimeout(() => {
                $this.find('#_content')?.find('.dismissible-auto')?.fadeOutAndRemove(400);
            }, 4000);
        })
        .always(function() {
            btn_submit.prop('disabled', false).html(button_text);
        });
    });

    //---------------------------------------------------------------
    // filter tabs
    //---------------------------------------------------------------

    const $filterTabs = $('.filter-tabs');
    $filterTabs.each(function() {
        const $el = $(this),
            $nav = $el.find('.tabs-nav'),
            $content = $el.find('.tabs-content'),
            $tabs = $nav.find('a'),
            initialHash = window.location.hash;

        const activateTab = (hash) => {
            const $tab = $nav.find(`a[href="${hash}"]`);
            $nav.find('a').removeClass('current');
            $content.find('.tabs-panel').hide();

            if ($tab.length) {
                $tab.addClass('current');
                $(hash).show();
            } else {
                $tabs.first().addClass('current');
                $content.find('.tabs-panel').first().show();
                window.history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        };

        activateTab(initialHash || $tabs.first().attr('href'));

        $nav.on('click', 'a', function(e) {
            e.preventDefault();
            const hash = $(this).attr('href');
            window.location.hash = hash;
            activateTab(hash);
            $('html, body').animate({ scrollTop: $el.offset().top - $('header').outerHeight() }, 300);
        });

        $(window).on('hashchange', function() {
            activateTab(window.location.hash || $tabs.first().attr('href'));
        });
    });

    //---------------------------------------------------------------
    // select2
    //---------------------------------------------------------------

    // select2 multiple
    const select2_multiple = $('.select2-multiple');
    $.each(select2_multiple, function(i, el) {
        $(el).select2({
            multiple: true,
            allowClear: true,
            width: 'resolve',
            dropdownAutoWidth: true,
            placeholder: $(el).attr('placeholder'),
        });
    });

    // select2 tags
    const select2_tags = $('.select2-tags');
    $.each(select2_tags, function(i, el) {
        $(el).select2({
            multiple: true,
            tags: true,
            allowClear: true,
            width: 'resolve',
            dropdownAutoWidth: true,
            placeholder: $(el).attr('placeholder'),
        });
    });

    // select2 IPs
    const select2_ips = $('.select2-ips');
    $.each(select2_ips, function(i, el) {
        $(el).select2({
            multiple: true,
            tags: true,
            allowClear: true,
            width: 'resolve',
            dropdownAutoWidth: true,
            placeholder: $(el).attr('placeholder'),
            createTag: function(params) {
                let term = $.trim(params.term);

                // Validate the term as an IP address or range
                if (isValidIPRange(term)) {
                    return {
                        id: term,
                        text: term,
                    };
                } else {
                    return null;
                }
            },
        });
    });

    // select2 emails
    const select2_emails = $('.select2-emails');
    $.each(select2_emails, function(i, el) {
        $(el).select2({
            multiple: true,
            tags: true,
            allowClear: true,
            width: 'resolve',
            dropdownAutoWidth: true,
            placeholder: $(el).attr('placeholder'),
            createTag: function(params) {
                let term = $.trim(params.term);
                if (isValidEmail(term)) {
                    return {
                        id: term,
                        text: term,
                    };
                } else {
                    return null;
                }
            },
        });
    });
});

/**
 * Validate email address
 *
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

/**
 * validate IP range (IPv4)
 *
 * @param range
 * @returns {boolean}
 */
function isValidIPRange(range) {
    const ipPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/;
    const rangePattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/;
    const cidrPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\d|[1-2]\d|3[0-2])$/;

    if (ipPattern.test(range)) {
        return true;
    }

    if (rangePattern.test(range)) {
        const [ startIP, endRange ] = range.split('-');
        const endIP = startIP.split('.').slice(0, 3).join('.') + '.' + endRange;
        return compareIPs(startIP, endIP) < 0;
    }

    return cidrPattern.test(range);
}

/**
 * compare two IP addresses
 *
 * @param ip1
 * @param ip2
 * @returns {number}
 */
function compareIPs(ip1, ip2) {
    const ip1Parts = ip1.split('.').map(Number);
    const ip2Parts = ip2.split('.').map(Number);

    for (let i = 0; i < 4; i++) {
        if (ip1Parts[i] < ip2Parts[i]) return -1;
        if (ip1Parts[i] > ip2Parts[i]) return 1;
    }
    return 0;
}
