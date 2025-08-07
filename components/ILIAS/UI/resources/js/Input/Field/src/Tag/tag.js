/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

/**
 * @type {number}
 */
const tagAutocompleteTriggerTimeout = 200;

/**
 * @type {string}
 */
const tagAccessibilityContainerClass = 'c-field-tag__assistive-text';

/**
 * @type {string}
 */
const tagSortablePlaceholderClass = 'c-field-tag__dropzone';

/**
 * @param {HTMLInput} input
 * @param {Object} config
 * @returns {Object}
 */
function buildSettings(inputId, config) {
  return {
    id: inputId,
    whitelist: config.options,
    enforceWhitelist: !config.userInput,
    duplicates: config.allowDuplicates,
    maxTags: config.maxItems,
    delimiters: null,
    a11y: {
      focusableTags: true,
    },
    originalInputValueFormat: (valuesArr) => valuesArr.map((item) => item.value),
    dropdown: {
      enabled: config.dropdownSuggestionsStartAfter,
      maxItems: config.dropdownMaxItems,
      closeOnSelect: config.dropdownCloseOnSelect,
      highlightFirst: config.highlight,
    },
    transformTag(tagData) {
      if (!tagData.display) {
        tagData.display = tagData.value;
        tagData.value = encodeURI(tagData.value);
      }
      tagData.display = tagData.display
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    },
    templates: {
      tag(tagData) {
        return `<tag contenteditable='false'
          spellcheck="false" class='c-field-tag__tag tagify__tag'
          aria-describedby="${inputId}-operation"
          value="${tagData.value}"
          tabindex="0">
          <x title='remove tag' class='tagify__tag__removeBtn'></x>
          <div>
              <span class='tagify__tag-text'>${tagData.display}</span>
          </div>
        </tag>`;
      },
      dropdownItem(tagData) {
        return `<div class='tagify__dropdown__item' tagifySuggestionIdx="${tagData.tagifySuggestionIdx}" value="${tagData.value}">
          <span>${tagData.display}</span>
          </div>`;
      },
    },
  };
}

/**
 * @param {Tagify} instance
 * @param {Object} context
 * @param {URL} autocompleteEndpoint
 * @param {InputEvent} event
 * @returns {void}
 */
function retrieveAutocomplete(
  instance,
  context,
  autocompleteEndpoint,
  event,
) {
  context.controller.abort();
  context.controller = new AbortController();

  instance.whitelist = null;

  if (typeof context.timeout === 'number') {
    instance.DOM.scope.ownerDocument.defaultView.clearTimeout(context.timeout);
    context.timeout = undefined;
  }

  if (event.detail.value.length < context.suggestionsStartAfter) {
    return;
  }

  context.timeout = instance.DOM.scope.ownerDocument.defaultView.setTimeout(
    () => {
      const searchTerm = event.detail.value;
      autocompleteEndpoint.searchParams.append('term', searchTerm);
      instance.loading(true);
      fetch(autocompleteEndpoint.toString(), { signal: context.timeout.signal })
        .then((answer) => answer.json())
        .catch(() => {})
        .then((options) => {
          instance.whitelist = options;
          instance.loading(false).dropdown.show(searchTerm);
        });
    },
    tagAutocompleteTriggerTimeout,
  );
}

/**
 * @param {Tagify} instance
 * @returns {void}
 */
function removePlaceholders(instance) {
  instance.DOM.scope.querySelectorAll(`.${tagSortablePlaceholderClass}`).forEach(
    (elem) => {
      instance.DOM.scope.removeChild(elem);
    }
  );
}

/**
 * @param {Tagify} instance
 * @param {HTMLElement} draggedElement
 * @returns {void}
 */
function onDragStart(instance, draggedElement) {
  removePlaceholders(instance);

  const style = draggedElement.ownerDocument.defaultView.getComputedStyle(draggedElement);
  const dropzone = instance.DOM.scope.ownerDocument.createElement('div');
  dropzone.classList.add(tagSortablePlaceholderClass);
  dropzone.style.height = style.height;
  dropzone.style.width = style.width;
  dropzone.style.marginRight = style.marginRight;
  dropzone.style.marginBottom = style.marginBottom;
  dropzone.style.marginLeft = style.marginLeft;
  dropzone.style.marginTop = style.marginTop;
  instance.DOM.scope.querySelectorAll(`.${instance.settings.classNames.tag}`).forEach(
    (elem) => {
      if (elem === draggedElement) {
        return;
      }
      if (elem.previousElementSibling !== draggedElement
        && !elem.previousElementSibling?.classList.contains(tagSortablePlaceholderClass)) {
        elem.parentNode.insertBefore(dropzone.cloneNode(true), elem);
      }

      if (elem.nextElementSibling === draggedElement) {
        return;
      }

      elem.parentNode.insertBefore(dropzone.cloneNode(true), elem.nextElementSibling);
    },
  );
}

/**
 * @param {Tagify} instance
 * @param {KeyEvent} event
 * @returns {void}
 */
function deleteTagOnKeypress(instance, event) {
  if (event.key === 'Delete' && event.target.dataset.selected !== 'true') {
    instance.removeTags(event.target);
    return;
  }
}

/**
 * @param {Tagify} instance
 * @returns {void}
 */
function onChange(instance) {
  removePlaceholders(instance);
  instance.updateValueByDOMTags();
}

/**
 * @param {Tagify} instance
 * @returns {void}
 */
function addEventListenersForDeletion(instance) {
  instance.getTagElms().forEach((elem) => {
    elem.addEventListener(
      'keydown',
      (event) => { deleteTagOnKeypress(instance, event); },
    );
  });
  instance.on('add', (e) => {
    e.detail.tag.addEventListener(
      'keydown',
      (event) => { deleteTagOnKeypress(instance, event); },
    );
  });
}

/**
 * @param {Tagify} Tagify
 * @param {function} makeDraggable
 * @param {HTMLInput} input
 * @param {Object} config
 * @param {array} value
 * @returns {void}
 */
export default function init(Tagify, makeDraggable, input, config, value) {
  const instance = new Tagify(
    input,
    buildSettings(input.id, config),
  );
  instance.addTags(value);
  addEventListenersForDeletion(instance);
  if (config.autocompleteEndpoint !== null) {
    const context = {
      controller: new AbortController(),
      timeout : undefined,
      suggestionsStartAfter: config.suggestionStarts,
    };
    instance.on('input', (event) => {
      retrieveAutocomplete(
        instance,
        context,
        new URL(config.autocompleteEndpoint),
        event,
      );
    });
  }
  if (config.orderable) {
    makeDraggable(
      'move',
      instance.DOM.scope,
      instance.settings.classNames.tag,
      tagSortablePlaceholderClass,
      {
        infoContainer: instance.DOM.scope.previousElementSibling,
        texts: {
          default() {
            return config.accessibilityInfo.default;
          },
          tagSelected(selectedTag) {
            return config.accessibilityInfo.tagSelected.replace(
              '%s',
              instance.getTagTextNode(selectedTag).innerText
            );
          },
          position(selectedPlaceholder) {
            if (selectedPlaceholder.previousElementSibling === null) {
              return config.accessibilityInfo.positionInfoFirst;
            }

            return config.accessibilityInfo.positionInfo.replace(
              '%s',
              instance.getTagTextNode(selectedPlaceholder.previousSibling).innerText
            );
          }
        },
      },
      (draggedElement) => { onDragStart(instance, draggedElement); },
      () => { onChange(instance) },
    );
  }
}
