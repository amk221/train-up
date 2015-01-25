/* global jQuery, tb_remove, tb_show, window */

jQuery(function($) {
  'use strict';

  /**
   * Button elements that launch the modal box that allows for browsing images
   */
  var $imageBrowseButtons = $('.tu-image-browse');

  /**
   * - Callback fired when an image browse button is clicked
   * - Load the buttons parent element
   * - Show the modal dialog
   * - Override the send_to_editor function (seems like the only way)
   *   when this is fired we pull out the image src of the selected image
   *   and update the preview image.
   *
   * @param {object} e Click event
   */
  var showBrowseDialog = function(e) {
    e.preventDefault();

    var $imageBrowser = $(this).parent('.tu-image-browser');

    window.send_to_editor = function(html) {
      var src    = $('img', html).attr('src');
      var $field = $imageBrowser.find('input');
      var $img   = $imageBrowser.find('img');

      $field.val(src);
      $img.attr('src', src);
      $imageBrowser.addClass('tu-has-image');

      tb_remove();
    };

    tb_show('', 'media-upload.php?type=image&tab=library&TB_iframe=true');
  };

  /**
   * When any image browse button is clicked, show the modal dialog
   */
  $imageBrowseButtons.click(showBrowseDialog);

});