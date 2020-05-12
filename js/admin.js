(function($) {
	if (!window['cuAdmin']) { //To compile for checking: java -jar /usr/local/bin/closure.jar --js=admin.js --js_output_file=test.js

		window['cuAdmin'] = {
			isSmallScreen: false,
			loading16: '<div class="cuLoading16"></div>',
			loadingCount: 0,
			colorboxQueue: [],
			mode: '',
			reloadConfigPage: false,
			nonce: false,
			debugOn: false,
			_windowHasFocus: true,
			basePageName: '',

			init: function() {
				this.isSmallScreen = window.matchMedia("only screen and (max-width: 500px)").matches;

				this.nonce = CUAdminVars.firstNonce;
				this.debugOn = CUAdminVars.debugOn == '1' ? true : false;
				this.basePageName = document.title;

				var self = this;

				$(window).on('blur', function() {
					self._windowHasFocus = false;
				}).on('focus', function() {
					self._windowHasFocus = true;
				}).focus();

				$('.do-show').click(function() {
					var $this = $(this);
					$this.hide();
					$($this.data('selector')).show();
					return false;
				});

				$(window).bind("scroll", function() {
					$(this).scrollTop() > 200 ? $(".cu-scrollTop").fadeIn() : $(".cu-scrollTop").fadeOut()
				});

				$(".cu-scrollTop").click(function(e) {
					return e.stopPropagation(), $("body,html").animate({
						scrollTop: 0
					}, 800), !1;
				});

				var tabs = jQuery('#cuTopTabs').find('a');

				if (tabs.length > 0) {
					tabs.click(function() {
						jQuery('#cuTopTabs').find('a').removeClass('nav-tab-active');
						jQuery('.cuTopTab').removeClass('active');
						jQuery(this).addClass('nav-tab-active');

						var tab = jQuery('#' + jQuery(this).attr('id').replace('-tab', ''));
						tab.addClass('active');
						jQuery('#cuHeading').html(tab.data('title'));
						jQuery('#cuTopTabsMobileTitle').text(jQuery(this).text());
						document.title = tab.data('title') + " \u2039 " + self.basePageName;
						self.sectionInit();
					});

					if (window.location.hash) {
						var hashes = window.location.hash.split('#');
						var hash = hashes[hashes.length - 1];
						for (var i = 0; i < tabs.length; i++) {
							if (hash == jQuery(tabs[i]).attr('id').replace('-tab', '')) {
								jQuery(tabs[i]).trigger('click');
							}
						}
					}
					else {
						jQuery(tabs[0]).trigger('click');
					}

					jQuery(window).on('hashchange', function () {
						var hashes = window.location.hash.split('#');
						var hash = hashes[hashes.length - 1];
						for (var i = 0; i < tabs.length; i++) {
							if (hash == jQuery(tabs[i]).attr('id').replace('-tab', '')) {
								jQuery(tabs[i]).trigger('click');
							}
						}
					});

				} else {
					this.sectionInit();
				}

				if (this.mode) {
					jQuery(document).bind('cbox_closed', function() {
						self.colorboxIsOpen = false;
						self.colorboxServiceQueue();
					});
				}

				$(document).focus();

				/*
				 * Image Uploading/Save Buttons
				 */
				function rotate_buttons(attr_id='') {
					var image_path = $('#upload_image').val();

					if(image_path.length > 30 || attr_id.length > 0){
						$('#upload_image_button').hide();
						$('#save_image_btn').show();
					}else{
						$('#upload_image_button').show();
						$('#save_image_btn').hide();
					}
				}

				$('#upload_image_button').click(function(){
					rotate_buttons('#upload_image_button');
				});

				$('#upload_image').keyup(function(){
					rotate_buttons();
				});

			}, // END INIT


			sectionInit: function() {
				var self = this;
				this.mode = false;

				if (jQuery('#cuMode_dashboard:visible').length > 0) {
					this.mode = 'dashboard';

				} else if (jQuery('#cuMode_options:visible').length > 0) {
					this.mode = 'options';

				}

				if (this.mode) { //We are in a cu page

				}

			},

			updateSwitch: function(elemID, configItem, cb) {
				var setting = jQuery('#' + elemID).is(':checked');
				this.updateConfig(configItem, jQuery('#' + elemID).is(':checked') ? 1 : 0, cb);
			},

			setupSwitches: function(elemID, configItem, cb) {

				jQuery('.cuOnOffSwitch-checkbox').change(function() {
					jQuery.data(this, 'lastSwitchChange', (new Date()).getTime());
				});
				var self = this;
				jQuery('div.cuOnOffSwitch').mouseup(function() {
					var elem = jQuery(this);
					setTimeout(function() {
						var checkedElem = elem.find('.cuOnOffSwitch-checkbox');
						if ((new Date()).getTime() - jQuery.data(checkedElem[0], 'lastSwitchChange') > 300) {
							checkedElem.prop('checked', !checkedElem.is(':checked'));
							self.updateSwitch(elemID, configItem, cb);
						}
					}, 50);
				});
			},

			showLoading: function() {
				this.loadingCount++;
				if (this.loadingCount == 1) {
					//jQuery('<div id="cuWorking">cu is working...</div>').appendTo('body');
					jQuery('<div id="cuLoading"><img src="'+CUAdminVars.baseURL+'images/loading-circle.gif" /></div>').appendTo('body');
				}
			},

			removeLoading: function() {
				this.loadingCount--;
				if (this.loadingCount == 0) {
					jQuery('#cuLoading').remove();
				}
			},

			updateSignaturesTimestamp: function(signatureUpdateTime) {
				var date = new Date(signatureUpdateTime * 1000);

				var dateString = date.toString();
				if (date.toLocaleString) {
					dateString = date.toLocaleString();
				}

				var sigTimestampEl = $('#cu-scan-sigs-last-update');
				var newText = 'Last Updated: ' + dateString;
				if (sigTimestampEl.text() !== newText) {
					sigTimestampEl.text(newText)
						.css({
							'opacity': 0
						})
						.animate({
							'opacity': 1
						}, 500);
				}
			},

			ajax: function(action, data, cb, cbErr, noLoading) {
				if (typeof(data) == 'string') {
					if (data.length > 0) {
						data += '&';
					}
					data += 'action=' + action + '&nonce=' + this.nonce;
				} else if (typeof(data) == 'object' && data instanceof Array) {
					// jQuery serialized form data
					data.push({
						name: 'action',
						value: action
					});
					data.push({
						name: 'nonce',
						value: this.nonce
					});
				} else if (typeof(data) == 'object') {
					data['action'] = action;
					data['nonce'] = this.nonce;
				}
				if (!cbErr) {
					cbErr = function() {
					};
				}
				var self = this;
				if (!noLoading) {
					this.showLoading();
				}

				// console.log(data);

				jQuery.ajax({
					type: 'POST',
					url: CUAdminVars.ajaxURL,
					dataType: "json",
					data: data,
					success: function(json) {
						if (!noLoading) {
							self.removeLoading();
						}
						if (json && json.nonce) {
							self.nonce = json.nonce;
						}
						if (json && json.errorMsg) {
							self.colorbox((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', json.errorMsg);
						}
						cb(json);
					},
					error: function(request, error) {
						console.log(arguments);

						if (!noLoading) {
							self.removeLoading();
						}
						cbErr();
					}
				});
			},

			colorbox: function(width, heading, body, settings) {
				if (typeof settings === 'undefined') {
					settings = {};
				}
				this.colorboxQueue.push([width, heading, body, settings]);
				this.colorboxServiceQueue();
			},

			colorboxServiceQueue: function() {
				if (this.colorboxIsOpen) {
					return;
				}
				if (this.colorboxQueue.length < 1) {
					return;
				}
				var elem = this.colorboxQueue.shift();
				this.colorboxOpen(elem[0], elem[1], elem[2], elem[3]);
			},

			colorboxOpen: function(width, heading, body, settings) {
				var self = this;
				this.colorboxIsOpen = true;
				jQuery.extend(settings, {
					width: width,
					html: "<h3>" + heading + "</h3><p>" + body + "</p>",
					onClosed: function() {
						self.colorboxClose();
					}
				});
				jQuery.colorbox(settings);
			},

			colorboxClose: function() {
				this.colorboxIsOpen = false;
				jQuery.colorbox.close();
			},

			es: function(val) {
				if (val) {
					return val;
				} else {
					return "";
				}
			},

			noQuotes: function(str) {
				return str.replace(/"/g, '&#34;').replace(/\'/g, '&#145;');
			},

			commify: function(num) {
				return ("" + num).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
			},

			ucfirst: function(str) {
				str = "" + str;
				return str.charAt(0).toUpperCase() + str.slice(1);
			},


			makeDiffLink: function(dat) {
				return CUAdminVars.siteBaseURL + '?_cusf=diff&nonce=' + this.nonce +
					'&file=' + encodeURIComponent(this.es(dat['file'])) +
					'&cType=' + encodeURIComponent(this.es(dat['cType'])) +
					'&cKey=' + encodeURIComponent(this.es(dat['cKey'])) +
					'&cName=' + encodeURIComponent(this.es(dat['cName'])) +
					'&cVersion=' + encodeURIComponent(this.es(dat['cVersion']));
			},

			makeViewFileLink: function(file) {
				return CUAdminVars.siteBaseURL + '?_cusf=view&nonce=' + this.nonce + '&file=' + encodeURIComponent(file);
			},

			makeViewOptionLink: function(option, siteID) {
				return CUAdminVars.siteBaseURL + '?_cusf=viewOption&nonce=' + this.nonce + '&option=' + encodeURIComponent(option) + '&site_id=' + encodeURIComponent(siteID);
			},

			makeTimeAgo: function(t) {
				var months = Math.floor(t / (86400 * 30));
				var days = Math.floor(t / 86400);
				var hours = Math.floor(t / 3600);
				var minutes = Math.floor(t / 60);
				if (months > 0) {
					days -= months * 30;
					return this.pluralize(months, 'month', days, 'day');
				} else if (days > 0) {
					hours -= days * 24;
					return this.pluralize(days, 'day', hours, 'hour');
				} else if (hours > 0) {
					minutes -= hours * 60;
					return this.pluralize(hours, 'hour', minutes, 'min');
				} else if (minutes > 0) {
					//t -= minutes * 60;
					return this.pluralize(minutes, 'minute');
				} else {
					return Math.round(t) + " seconds";
				}
			},

			pluralize: function(m1, t1, m2, t2) {
				if (m1 != 1) {
					t1 = t1 + 's';
				}
				if (m2 != 1) {
					t2 = t2 + 's';
				}
				if (m1 && m2) {
					return m1 + ' ' + t1 + ' ' + m2 + ' ' + t2;
				} else {
					return m1 + ' ' + t1;
				}
			},

			makeElemID: function() {
				return 'cuElemGen' + this.elementGeneratorIter++;
			},

			pulse: function(sel) {
				jQuery(sel).fadeIn(function() {
					setTimeout(function() {
						jQuery(sel).fadeOut();
					}, 2000);
				});
			},

			saveConfig: function() {
				var qstr = jQuery('#cuConfigForm').serialize();
				var self = this;
				jQuery('.cuSavedMsg').hide();
				jQuery('.cuAjax24').show();

				this.ajax('ccollinsupdater_saveConfig', qstr, function(res) {
					jQuery('.cuAjax24').hide();
					if (res.ok) {
						if (res['reload'] == 'reload' || CUA.reloadConfigPage) {
							self.colorbox((self.isSmallScreen ? '300px' : '400px'), "Please reload this page", "You selected a config option that requires a page reload. Click the button below to reload this page to update the menu.<br /><br /><center><input class='cu-btn cu-btn-default' type='button' name='cuReload' value='Reload page' onclick='window.location.reload(true);' /></center>");
							return;
						} else {
							self.pulse('.cuSavedMsg');
						}

					} else if (res.errorMsg) {
						return;
					} else {
						self.colorbox((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'We encountered an error trying to save your changes.');
					}
				});
			},

			savePartialConfig: function(formSelector) {

				var qstr = jQuery(formSelector).serialize();

				jQuery(formSelector).find('input:checkbox:not(:checked)').each(function(idx, el) {
					qstr += '&' + encodeURIComponent(jQuery(el).attr('name')) + '=0';
				});

				var self = this;

				jQuery('.cuSavedMsg').hide();
				jQuery('.cuAjax24').show();

				this.ajax('ccollinsupdater_savePartialConfig', qstr, function(res) {
					jQuery('.cuAjax24').hide();
					if (res.ok) {
						self.pulse('.cuSavedMsg');
					} else if (res.errorMsg) {
						return;
					} else {
						self.colorbox((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'We encountered an error trying to save your changes.');
					}
				});
			},

			updateConfig: function(key, val, cb) {
				this.ajax('ccollinsupdater_updateConfig', {key: key, val: val}, function(ret) {
					if (cb) {
						cb(ret);
					}
				});
			},

			saveDebuggingConfig: function() {
				var qstr = jQuery('#cuDebuggingConfigForm').serialize();
				var self = this;
				jQuery('.cuSavedMsg').hide();
				jQuery('.cuAjax24').show();
				this.ajax('ccollinsupdater_saveDebuggingConfig', qstr, function(res) {
					jQuery('.cuAjax24').hide();
					if (res.ok) {
						self.pulse('.cuSavedMsg');
					} else if (res.errorMsg) {
						return;
					} else {
						self.colorbox((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'We encountered an error trying to save your changes.');
					}
				});
			},

			getQueryParam: function(name) {
				name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
				var regexS = "[\\?&]" + name + "=([^&#]*)";
				var regex = new RegExp(regexS);
				var results = regex.exec(window.location.search);
				if (results == null) {
					return "";
				} else {
					return decodeURIComponent(results[1].replace(/\+/g, " "));
				}
			},

			inet_aton: function(dot) {
				var d = dot.split('.');
				return ((((((+d[0]) * 256) + (+d[1])) * 256) + (+d[2])) * 256) + (+d[3]);
			},

			inet_ntoa: function(num) {
				var d = num % 256;
				for (var i = 3; i > 0; i--) {
					num = Math.floor(num / 256);
					d = num % 256 + '.' + d;
				}
				return d;
			},

			inet_pton: function(a) {
				//  discuss at: http://phpjs.org/functions/inet_pton/
				// original by: Theriault
				//   example 1: inet_pton('::');
				//   returns 1: '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0'
				//   example 2: inet_pton('127.0.0.1');
				//   returns 2: '\x7F\x00\x00\x01'

				var r, m, x, i, j, f = String.fromCharCode;
				m = a.match(/^(?:\d{1,3}(?:\.|$)){4}/); // IPv4
				if (m) {
					m = m[0].split('.');
					m = f(m[0]) + f(m[1]) + f(m[2]) + f(m[3]);
					// Return if 4 bytes, otherwise false.
					return m.length === 4 ? m : false;
				}
				r = /^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i;
				m = a.match(r); // IPv6
				if (m) {
					if (a == '::') {
						return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
					}

					var colonCount = a.split(':').length - 1;
					var doubleColonPos = a.indexOf('::');
					if (doubleColonPos > -1) {
						var expansionLength = ((doubleColonPos == 0 || doubleColonPos == a.length - 2) ? 9 : 8) - colonCount;
						var expansion = '';
						for (i = 0; i < expansionLength; i++) {
							expansion += ':0000';
						}
						a = a.replace('::', expansion + ':');
						a = a.replace(/(?:^\:|\:$)/, '', a);
					}

					var ipGroups = a.split(':');
					var ipBin = '';
					for (i = 0; i < ipGroups.length; i++) {
						var group = ipGroups[i];
						if (group.length > 4) {
							return false;
						}
						group = ("0000" + group).slice(-4);
						var b1 = parseInt(group.slice(0, 2), 16);
						var b2 = parseInt(group.slice(-2), 16);
						if (isNaN(b1) || isNaN(b2)) {
							return false;
						}
						ipBin += f(b1) + f(b2);
					}

					return ipBin.length == 16 ? ipBin : false;
				}
				return false; // Invalid IP.
			},

			inet_ntop: function(a) {
				//  discuss at: http://phpjs.org/functions/inet_ntop/
				// original by: Theriault
				//   example 1: inet_ntop('\x7F\x00\x00\x01');
				//   returns 1: '127.0.0.1'
				//   example 2: inet_ntop('\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\1');
				//   returns 2: '::1'

				var i = 0,
					m = '',
					c = [];
				a += '';
				if (a.length === 4) { // IPv4
					return [
						a.charCodeAt(0), a.charCodeAt(1), a.charCodeAt(2), a.charCodeAt(3)].join('.');
				} else if (a.length === 16) { // IPv6
					for (i = 0; i < 16; i++) {
						c.push(((a.charCodeAt(i++) << 8) + a.charCodeAt(i))
							.toString(16));
					}
					return c.join(':')
						.replace(/((^|:)0(?=:|$))+:?/g, function(t) {
							m = (t.length > m.length) ? t : m;
							return t;
						})
						.replace(m || ' ', '::');
				} else { // Invalid length
					return false;
				}
			},

			windowHasFocus: function() {
				if (typeof document.hasFocus === 'function') {
					return document.hasFocus();
				}
				// Older versions of Opera
				return this._windowHasFocus;
			},

			htmlEscape: function(html) {
				return String(html)
					.replace(/&/g, '&amp;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#39;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;');
			},

			showTimestamp: function(timestamp, serverTime, format) {
				serverTime = serverTime === undefined ? new Date().getTime() / 1000 : serverTime;
				format = format === undefined ? '${dateTime} (${timeAgo} ago)' : format;
				var date = new Date(timestamp * 1000);

				return jQuery.tmpl(format, {
					dateTime: date.toLocaleDateString() + ' ' + date.toLocaleTimeString(),
					timeAgo: this.makeTimeAgo(serverTime - timestamp)
				});
			},

			updateTimeAgo: function() {
				var self = this;
				jQuery('.cuTimeAgo-timestamp').each(function(idx, elem) {
					var el = jQuery(elem);
					var testEl = el;
					if (typeof jQuery === "function" && testEl instanceof jQuery) {
						testEl = testEl[0];
					}

					var rect = testEl.getBoundingClientRect();
					if (!(rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth))) {
						return;
					}

					var timestamp = el.data('cuctime');
					if (!timestamp) {
						timestamp = el.attr('data-timestamp');
					}
					var serverTime = self.serverMicrotime;
					var format = el.data('cuformat');
					if (!format) {
						format = el.attr('data-format');
					}
					el.html(self.showTimestamp(timestamp, serverTime, format));
				});
			},

			dateFormat: function(date) {
				if (date instanceof Date) {
					if (date.toLocaleString) {
						return date.toLocaleString();
					}
					return date.toString();
				}
				return date;
			},

			base64_decode: function(s) {
				var e = {}, i, b = 0, c, x, l = 0, a, r = '', w = String.fromCharCode, L = s.length;
				var A = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
				for (i = 0; i < 64; i++) {
					e[A.charAt(i)] = i;
				}
				for (x = 0; x < L; x++) {
					c = e[s.charAt(x)];
					b = (b << 6) + c;
					l += 6;
					while (l >= 8) {
						((a = (b >>> (l -= 8)) & 0xff) || (x < (L - 2))) && (r += w(a));
					}
				}
				return r;
			}
		};

		window['CUA'] = window['cuAdmin'];

		setInterval(function() {
			CUAD.updateTimeAgo();
		}, 1000);

	}
	jQuery(function() {
		cuAdmin.init();
	});

})(jQuery);
