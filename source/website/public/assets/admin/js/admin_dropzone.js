(function ($) {
  "use strict";

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // Dropzone initialization
  Dropzone.options.myDropzone = {
    acceptedFiles: '.png, .jpg, .jpeg',
    maxFilesize: 12,
    transformFile: function (file, done) {
      if (typeof window.booktkitProcessEventImage === 'function') {
        window.booktkitProcessEventImage(file, 1170, 570, function (processed) { done(processed); });
      } else { done(file); }
    },
    url: storeUrl,
    error: function (file, response) {
      let message = 'Image upload failed.';

      if (typeof response === 'string') {
        message = response;
      } else if (response && typeof response === 'object') {
        if (typeof response.message === 'string' && response.message.trim() !== '') {
          message = response.message;
        } else if (typeof response.msg === 'string' && response.msg.trim() !== '') {
          message = response.msg;
        } else if (response.errors) {
          const keys = Object.keys(response.errors);
          if (keys.length && Array.isArray(response.errors[keys[0]]) && response.errors[keys[0]].length) {
            message = response.errors[keys[0]][0];
          }
        } else if (Array.isArray(response.file) && response.file.length) {
          message = response.file[0];
        }
      }

      var content = {};
      content.message = message;
      content.title = 'Error';
      content.icon = 'fa fa-bell';

      $.notify(content, {
        type: 'warning',
        placement: {
          from: 'top',
          align: 'right'
        },
        showProgressbar: true,
        time: 1000,
        delay: 4000
      });

      this.removeFile(file);
    },
    success: function (file, response) {
      file.serverFileId = response.file_id;
      if (response.status == 'error') {

        var content = {};

        content.message = response.msg;
        content.title = 'Error';
        content.icon = 'fa fa-bell';
        $.notify(content, {
          type: 'warning',
          placement: {
            from: 'top',
            align: 'right'
          },
          showProgressbar: true,
          time: 1000,
          delay: 4000
        });
        return false;
      }

      $("#sliders").append(`<input type="hidden" name="slider_images[]" id="slider${response.file_id}" value="${response.file_id}">`);
      window.dispatchEvent(new CustomEvent("booktkit:gallery-uploaded", {detail: {id: response.file_id, preview_url: response.preview_url || ""}}));

      // Create the remove button
      var removeButton = Dropzone.createElement("<button type='button' class='rmv-btn'><i class='fa fa-times'></i></button>");

      // Capture the Dropzone instance as closure.
      var _this = this;
      // Listen to the click event
      removeButton.addEventListener("click", function (e) {
        // Make sure the button click doesn't submit the form:
        e.preventDefault();
        e.stopPropagation();

        _this.removeFile(file);

        rmvimg(response.file_id);
      });

      // Add the button to the file preview element.
      file.previewElement.appendChild(removeButton);

      if (typeof response.error != 'undefined') {
        _this.removeFile(file);
        if (typeof response.file != 'undefined') {
          let errorMsg = document.getElementById("errpreimg");
          errorMsg.innerHTML += `<div class="text-dark alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>${response.file[0]} </strong>
                                <button type="button" class="close drop_zone_close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`;
          setTimeout(function () {
            errorMsg.innerHTML = '';
          }, 1000000);
        }
      }
    }
  };

  window.booktkitRemoveGalleryImage = function(fileid) { rmvimg(fileid); };

  function rmvimg(fileid) {
    // If you want to the delete the file on the server as well,
    // you can do the AJAX request here.

    $.ajax({
      url: removeUrl,
      type: 'POST',
      data: {
        fileid: fileid
      },
      success: function (data) {
        $("#slider" + fileid).remove();
      }
    });

  }

  //   remove existing images
  $(document).on('click', '.rmvbtndb', function () {
    let indb = $(this).data('indb');
    $(".request-loader").addClass("show");
    $.ajax({
      url: rmvdbUrl,
      type: 'POST',
      data: {
        fileid: indb
      },
      success: function (data) {
        $(".request-loader").removeClass("show");
        var content = {};

        if (data == 'false') {
          $(".request-loader").removeClass("show");
          content.message = "You can't delete all images.!!";
          content.title = 'Warning';
        } else {
          $("#trdb" + indb).remove();
          content.message = 'Slider image deleted successfully!';
          content.title = 'Success';
        }

        content.icon = 'fa fa-bell';

        $.notify(content, {
          type: 'success',
          placement: {
            from: 'top',
            align: 'right'
          },
          showProgressbar: true,
          time: 1000,
          delay: 4000
        });
      }
    });
  });

  //   load event slider images
  if (loadImgs.length > 0) {
    $.get(loadImgs, function (data) {
      for (var i = 0; i < data.length; i++) {
        let msg = `<tr class="table-row" id="trdb${data[i].id}">
                            <td>
                                <img class="thumb-preview wf-150"
                                src="${baseUrl}/assets/admin/img/event-gallery/${data[i].image} " alt="slider image">
                            </td>
                            <td>
                                <i class="fas fa-times-circle rmvbtndb" data-indb="${data[i].id}"></i>
                            </td>
                        </tr>`;

        $("#img-table").append(msg);
      }
    });
  }

  if (typeof ProductloadImgs !== 'undefined') {
    //   load product slider images
    if (ProductloadImgs.length > 0) {
      $.get(ProductloadImgs, function (data) {
        for (var i = 0; i < data.length; i++) {
          let msg = `<tr class="table-row" id="trdb${data[i].id}">
                                <td>
                                    <img class="thumb-preview wf-150"
                                    src="${baseUrl}/assets/admin/img/product/gallery/${data[i].image} " alt="slider image">
                                </td>
                                <td>
                                    <i class="fas fa-times-circle rmvbtndb" data-indb="${data[i].id}"></i>
                                </td>
                            </tr>`;

          $("#img-table").append(msg);
        }
      });
    }

  }
})(jQuery);