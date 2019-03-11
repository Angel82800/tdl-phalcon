(function() {
  var ImageUploader;

  ImageUploader = (function() {
    ImageUploader.imagePath = '/img/support/placeholder.png';
    ImageUploader.imageSize = [400, 400];

    function ImageUploader(dialog) {
      var xhr, xhrComplete, xhrProgress;

      this._dialog = dialog;
      this._dialog.addEventListener('cancel', (function(_this) {
        return function() {
          return _this._onCancel();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.cancelupload', (function(_this) {
        return function() {
          return _this._onCancelUpload();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.clear', (function(_this) {
        return function() {
          return _this._onClear();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.fileready', (function(_this) {
        return function(ev) {
          return _this._onFileReady(ev.detail().file);
        };
      })(this));
      this._dialog.addEventListener('imageuploader.mount', (function(_this) {
        return function() {
          return _this._onMount();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.rotateccw', (function(_this) {
        return function() {
          return _this._onRotateCCW();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.rotatecw', (function(_this) {
        return function() {
          return _this._onRotateCW();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.save', (function(_this) {
        return function() {
          return _this._onSave();
        };
      })(this));
      this._dialog.addEventListener('imageuploader.unmount', (function(_this) {
        return function() {
          return _this._onUnmount();
        };
      })(this));
    }

    ImageUploader.prototype._onCancel = function() {};

    ImageUploader.prototype._onCancelUpload = function() {
      // Cancel the current upload

      // Stop the upload
      if (this._xhr) {
        this._xhr.upload.removeEventListener('progress', this._xhrProgress);
        this._xhr.removeEventListener('readystatechange', this._xhrComplete);
        this._xhr.abort();
      }

      // Set the dialog to empty
      return this._dialog.state('empty');
    };

    ImageUploader.prototype._onClear = function() {
      // Clear the current image

      ImageUploader.imagePath = '/uploads/contenttools/temporary.png';
      ImageUploader.imageSize = [600, 174];

      return this._dialog.clear();
    };

    ImageUploader.prototype._onFileReady = function(file) {
      // Upload a file to the server
      var formData, self = this;

      // Define functions to handle upload progress and completion
      this._xhrProgress = function (ev) {
        // Set the progress for the upload
        self._dialog.progress((ev.loaded / ev.total) * 100);
      }

      this._xhrComplete = function (ev) {
        var response;

        // Check the request is complete
        if (ev.target.readyState != 4) {
          return;
        }

        // Clear the request
        self._xhr = null
        self._xhrProgress = null
        self._xhrComplete = null

        // Handle the result of the upload
        if (parseInt(ev.target.status) == 200) {
          // Unpack the response (from JSON)
          response = JSON.parse(ev.target.responseText);

          ImageUploader.imagePath = response.url;
          ImageUploader.imageSize = response.size;

          // Populate the this._dialog
          return self._dialog.populate(ImageUploader.imagePath, ImageUploader.imageSize);
        } else {
          // The request failed, notify the user
          new ContentTools.FlashUI('no');
          return;
        }
      }

      // Set the this._dialog state to uploading and reset the progress bar to 0
      this._dialog.state('uploading');
      this._dialog.progress(0);

      // Build the form data to post to the server
      formData = new FormData();
      formData.append('image', file);

      // Make the request
      this._xhr = new XMLHttpRequest();
      this._xhr.upload.addEventListener('progress', this._xhrProgress);
      this._xhr.addEventListener('readystatechange', this._xhrComplete);
      this._xhr.open('POST', '/support/contenttools/fileready', true);
      this._xhr.send(formData);

      return;
    };

    ImageUploader.prototype._onMount = function() {};

    ImageUploader.prototype._onRotateCCW = function() {
      var clearBusy;
      this._dialog.busy(true);

      clearBusy = (function(_this) {
        return function() {
          return _this._dialog.busy(false);
        };
      })(this);

      return setTimeout(clearBusy, 1500);
    };

    ImageUploader.prototype._onRotateCW = function() {
      var clearBusy;
      this._dialog.busy(true);

      clearBusy = (function(_this) {
        return function() {
          return _this._dialog.busy(false);
        };
      })(this);

      return setTimeout(clearBusy, 1500);
    };

    ImageUploader.prototype._onSave = function() {
      var crop, cropRegion, formData, self = this;

      this._xhrComplete = function (ev) {
        var response;

        // Check the request is complete
        if (ev.target.readyState != 4) {
          return;
        }

        // Clear the request
        self._xhr = null
        self._xhrComplete = null

        // Free the dialog from its busy state
        self._dialog.busy(false);

        // Handle the result of the upload
        if (parseInt(ev.target.status) == 200) {
          // Unpack the response (from JSON)
          response = JSON.parse(ev.target.responseText);

          // Populate the this._dialog
          return self._dialog.save(
            response.url,
            response.size,
            {
              'alt': response.alt,
              'data-ce-max-width': response.size['width'],
            },
          );
        } else {
          // The request failed, notify the user
          new ContentTools.FlashUI('no');
          return;
        }
      }

      // Set the dialog to busy while the rotate is performed
      this._dialog.busy(true);

      // Build the form data to post to the server
      formData = new FormData();
      formData.append('url', ImageUploader.imagePath);

      // Set the width of the image when it's inserted, this is a default
      // the user will be able to resize the image afterwards.
      formData.append('width', 600);

      // Check if a crop region has been defined by the user
      if (this._dialog.cropRegion()) {
        formData.append('crop', this._dialog.cropRegion());
      }

      // Make the request
      this._xhr = new XMLHttpRequest();
      this._xhr.addEventListener('readystatechange', this._xhrComplete);
      this._xhr.open('POST', '/support/contenttools/save', true);
      this._xhr.send(formData);

      return;
    };

    ImageUploader.prototype._onUnmount = function() {};

    ImageUploader.createImageUploader = function(dialog) {
      return new ImageUploader(dialog);
    };

    return ImageUploader;

  })();

  window.ImageUploader = ImageUploader;

  window.onload = function() {
    var FIXTURE_TOOLS, editor, req;

    ContentTools.IMAGE_UPLOADER = ImageUploader.createImageUploader;

    editor = ContentTools.EditorApp.get();
    editor.init('[data-editable], [data-fixture]', 'data-name');

    // save handler
    editor.addEventListener('saved', function(ev) {
      var name, onStateChange, passive, payload, regions, xhr;

      // Check if this was a passive save
      passive = ev.detail().passive;

      // Check to see if there are any changes to save
      regions = ev.detail().regions;

      // if nothing has been modified or title is not set for new article, don't save
      if (Object.keys(regions).length == 0 || (edit_type == 'new' && Object.keys(regions).length < 2)) {
        return;
      }

      // Set the editors state to busy while we save our changes
      this.busy(true);

      // Collect the contents of each region into a FormData instance

      payload = new FormData();
      payload.append('type', edit_type);
      payload.append('identifier', identifier);
      payload.append('page', window.location.pathname);
      for (name in regions) {
        payload.append(name, regions[name]);
      }
      payload.append('images', JSON.stringify(getImages()));

      // Send the update content to the server to be saved
      onStateChange = function(ev) {
        // Check if the request is finished
        if (ev.target.readyState == 4) {
          editor.busy(false);
          if (ev.target.status == '200') {
            // Save was successful, notify the user with a flash
            if (!passive) {
              var response = JSON.parse(ev.target.response);

              if (response.status == 'success') {
                new ContentTools.FlashUI('ok');

                if (edit_type == 'new') {
                  window.location.href = '/support/view/' + response.article;
                }
              } else {
                console.log(response);
                new ContentTools.FlashUI('no');
              }
            }
          } else {
            // Save failed, notify the user with a flash
            new ContentTools.FlashUI('no');
          }
        }
      };

      xhr = new XMLHttpRequest();
      xhr.addEventListener('readystatechange', onStateChange);
      xhr.open('POST', '/support/contenttools/saveArticle');
      xhr.send(payload);
    });

    FIXTURE_TOOLS = [['undo', 'redo', 'remove']];

    ContentEdit.Root.get().bind('focus', function(element) {
      var tools;

      if (element.isFixed()) {
        tools = FIXTURE_TOOLS;
      } else {
        tools = ContentTools.DEFAULT_TOOLS;
      }

      if (editor.toolbox().tools() !== tools) {
        return editor.toolbox().tools(tools);
      }
    });

    function getImages() {
        // Return an object containing image URLs and widths for all regions
        var descendants, i, images;

        images = {};
        for (name in editor.regions()) {
            // Search each region for images
            descendants = editor.regions()[name].descendants();
            for (i = 0; i < descendants.length; i++) {
                // Filter out elements that are not images
                if (descendants[i].type() !== 'Image') {
                    continue;
                }
                images[descendants[i].attr('src')] = descendants[i].size()[0];
            }
        }

        return images;
    }

    // customized init button
    // document.getElementById('btn_ct_init').addEventListener('click', function() {
    //   editor.start();
    // }, false);

  };
}).call(this);
