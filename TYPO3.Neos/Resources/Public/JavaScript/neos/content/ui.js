/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'text!neos/templates/content/ui/saveIndicator.html',
	'neos/content/ui/elements'
],

function($, Ember, saveIndicatorTemplate) {
	if (window._requirejsLoadingTrace) {
		window._requirejsLoadingTrace.push('neos/content/ui');
	}

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};

	/**
	 * =====================
	 * SECTION: TREE PANEL
	 * =====================
	 */

		// Is necessary otherwise a button has always the class 'btn-mini'
	T3.Content.UI.ButtonDialog = Ember.View.extend(Ember.TargetActionSupport, {
		tagName: 'button',
		attributeBindings: ['disabled'],
		label: '',
		disabled: false,
		visible: true,
		icon: '',
		template: Ember.Handlebars.compile('{{#if view.icon}}<i class="{{unbound view.icon}}"></i> {{/if}}{{view.label}}'),

		click: function() {
			this.triggerAction();
		}
	});

	/**
	 * =====================
	 * SECTION: INSPECT TREE
	 * =====================
	 * - Inspect TreeButton
	 */


	T3.Content.UI.SaveIndicator = Ember.View.extend({
		saveRunning: false,
		lastSuccessfulTransfer: null,

		template: Ember.Handlebars.compile(saveIndicatorTemplate),

		lastSuccessfulTransferLabel: function() {
			var date = this.get('lastSuccessfulTransfer');
			if (date !== null) {
				function pad(n) {
					return n < 10 ? '0' + n : n;
				}
				return 'Saved at ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds())
			}
			return '';
		}.property('lastSuccessfulTransfer')
	});

	/**
	 * ================
	 * SECTION: UTILITY
	 * ================
	 * - Content Element Handle Utilities
	 */
	T3.Content.UI.Util = T3.Content.UI.Util || {};

	/**
	 * @param {object} $contentElement jQuery object for the element to which the handles should be added
	 * @param {integer} contentElementIndex The position in the collection on which paste / new actions should place the new entity
	 * @param {object} collection The VIE entity collection to which the element belongs
	 * @param {boolean} isSection Whether the element is a collection or not
	 * @return {object|void} The created Ember handle bar object
	 */
	T3.Content.UI.Util.AddContentElementHandleBars = function($contentElement, contentElementIndex, collection, isSection) {
		var handleContainerClassName, handleContainer;

		if (isSection === true) {
				// Add container BEFORE the contentcollection DOM element
			handleContainerClassName = 'neos-contentcollection-handle-container';
			if ($contentElement.prev() && $contentElement.prev().hasClass(handleContainerClassName)) {
				return;
			}
			handleContainer = $('<div />', {'class': 'neos ' + handleContainerClassName}).insertBefore($contentElement);

			return T3.Content.UI.SectionHandle.create({
				_element: $contentElement,
				_collection: collection,
				_entityCollectionIndex: contentElementIndex
			}).appendTo(handleContainer);
		}

			// Add container INTO the content elements DOM element
		handleContainerClassName = 'neos-contentelement-handle-container';
		if (!$contentElement || $contentElement.find('> .' + handleContainerClassName).length > 0) {
			return;
		}
		handleContainer = $('<div />', {'class': 'neos ' + handleContainerClassName}).prependTo($contentElement);

			// Make sure we have a minimum height to be able to hover
		if ($contentElement.height() < 16) {
			$contentElement.css('min-height', '16px');
		}

		return T3.Content.UI.ContentElementHandle.create({
			_element: $contentElement,
			_collection: collection,
			_entityCollectionIndex: contentElementIndex
		}).appendTo(handleContainer);
	};

	T3.Content.UI.Util.AddNotInlineEditableOverlay = function($element, entity) {
		var setOverlaySizeFn = function() {
				// We use a timeout here to make sure the browser has re-drawn; thus $element
				// has a possibly updated size
			window.setTimeout(function() {
				$element.find('> .neos-contentelement-overlay').css({
					'width': $element.width(),
					'height': $element.height()
				});
			}, 10);
		};

			// Add overlay to content elements without inline editable properties and no sub-elements
		if ($element.hasClass('neos-not-inline-editable')) {
			var overlay = $('<div />', {
				'class': 'neos-contentelement-overlay',
				'click': function(event) {
					if ($('.neos-primary-editor-action').length > 0) {
							// We need to use setTimeout here because otherwise the popover is aligned to the bottom of the body
						setTimeout(function() {
							$('.neos-primary-editor-action').click();
							if (Ember.View.views[$('.neos-primary-editor-action').attr('id')] && Ember.View.views[$('.neos-primary-editor-action').attr('id')].toggle) {
								Ember.View.views[$('.neos-primary-editor-action').attr('id')].toggle();
							}
						}, 1);
					}
					event.preventDefault();
				}
			}).insertBefore($element.find('> .neos-contentelement-handle-container'));

			$('<span />', {'class': 'neos-contentelement-overlay-icon'}).appendTo(overlay);

			setOverlaySizeFn();

			entity.on('change', function() {
					// If the entity changed, it might happen that the size changed as well; thus we need to reload the overlay size
				setOverlaySizeFn();
			});
		}
	};
});