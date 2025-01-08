wp.utils = wp.utils || {};
wp.felDigifact = wp.felDigifact || {};

/**
 * Returns a random integer between min (inclusive) and max (inclusive)
 *
 * @name GOU.utils.getRandomInt
 * @param   {Mixed} min Minimum number
 * @param   {Mixed} max Maximum number
 * @Returns {Number} Returns a random number
 */
wp.utils.getRandomInt = function (min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
};

/**
 * Closure to add a spinner element
 *
 * @name GOU.utils.spinner
 * @param   {Mixed} element or jQuery instance
 * @returns {Object} with the functions to handle the spinner
 */
wp.utils.spinner = (element) => {
  const $element = element instanceof jQuery ? element : jQuery(element);

  /** @const {String} spinner element random id */
  const SPINNER_ELEMENT_ID = "gp__spinner-" + wp.utils.getRandomInt(1, 100);

  const _spinner = jQuery("<i />", {
    id: SPINNER_ELEMENT_ID,
    class: "fa fa-cog fa-spin text-info mx-1 gp__spinner my-0-5",
    css: {
      display: "none",
    },
  });

  $element.append(_spinner);

  return {
    show: function () {
      _spinner.show();
    },
    hide: function () {
      _spinner.hide();
    },
    destroy: function () {
      _spinner.remove();
    },
  };
};
