/*global describe, it, expect, modules, spyOn*/
describe('Module ext.wikia.adEngine.pageFairDetection', function () {
	'use strict';

	function getModule(mocks) {
		return modules['ext.wikia.adEngine.pageFairDetection'](
				mocks.adContext,
				mocks.adTracker,
				mocks.scriptLoader,
				mocks.document,
				mocks.log,
				mocks.window
		);
	}

	function noop() { return noop }

	function getMocks() {
		return {
			adContext: {
				getContext: function () {
					return {
						opts: {
							pageFairWebsiteCode: 'TEST_CODE'
						}
					}
				}
			},
			adTracker: noop,
			scriptLoader: {
				loadAsync: noop
			},
			document: {
				dispatchEvents: noop,
				dispatchEvent: noop,
				getElementsByTagName: noop
			},
			log: noop,
			window: {
				ads: {
					runtime: {}
				}
			},
		};
	}

	it('shoud dispatch event after run pf_notify from external script', function () {
		var mocks = getMocks();
		var pageFairDetector = getModule(mocks);

		spyOn(mocks.document, 'dispatchEvent');
		mocks.window.pf_notify(true);

		expect(mocks.document.dispatchEvent).toHaveBeenCalled();
	});

	it('should change runtime parameter', function () {
		var mocks = getMocks();
		var pageFairDetector = getModule(mocks);

		spyOn(mocks.document, 'dispatchEvent');
		mocks.window.pf_notify(true);
		expect(mocks.window.ads.runtime.pf.blocking).toBe(true);

		mocks.window.pf_notify(false);
		expect(mocks.window.ads.runtime.pf.blocking).toBe(false);
	});

	it('should provide PageFair website code from context to external script', function () {
		var mocks = getMocks();
		var pageFairDetector = getModule(mocks);

		mocks.window.pf_notify(true);
		expect(mocks.window.bm_website_code).toBe('TEST_CODE');
	});

});
