jQuery( function ( $ ) {
  // here, $ is safe to use to reference jQuery
  MosaicHomeClass.init();
} );

var MosaicHomeClass = (function ( $ ) {
  // private variables here
  var $adminNav;
  var $sectionNavigator;
  var $sectionLightBox;
  var $classLightBox;
  var $libraryLightBox;
  /**
   * renderSection can close one of a few different lightboxes that are open.
   * $openLightBox is used to store which lightbox is open and should be closed.
   */
  var $openLightBox;
  var $saveLibraryLightBox;
  var $sectionSearch;
  var $classSearch;
  var $metaBoxControls;
  var $metaBoxSections;
  var $bucketWrapper;
  var $class_input;
  var sortedEditors;
  var subContentRegex = /(section\[\d+]\[.*?\]\[)(\d+)(.*)/g;
  var library         = [];
  var debounce        = {
    Yoast: undefined
  };

  var yoastPluginName = 'MosaicSectionsTheme';
  // selector to load all title, subtitle, and "text" areas for Yoast integration
  var contentSelector = 'input[type="text"][name*="_title"], input[type="text"][name*="headline"], textarea';
  var imageSelector   = 'input[type="hidden"][name*="\[image\]"]';

  // private functions here
  function bindEvents() {
    $( document ).on( 'click', '.collapse-sections-all', function () {
      var $el         = $( '.collapse-sections-all' );
      var $icon       = $el.find( '.fa' );
      var isCollapsed = $icon.hasClass( 'fa-expand' );
      var $icons      = $( '.collapse-section' ).find( '.fa' );
      var text        = 'Expand';

      if ( !isCollapsed ) {
        $( '.section', $metaBoxSections ).removeClass( 'collapsed' );
        $icons.trigger( 'click' );
        $icon.removeClass( 'fa-compress' ).addClass( 'fa-expand' );
      } else {
        $( '.section', $metaBoxSections ).addClass( 'collapsed' );
        $icons.trigger( 'click' );
        $icon.removeClass( 'fa-expand' ).addClass( 'fa-compress' );
        text = 'Collapse';
      }

      $icon.siblings( 'span' ).text( text );
      updateSectionState();
    } );

    $( document ).on( 'click', 'a.section-chooser', function () {
      toggleLightBox( $( '.lightbox-close', $sectionLightBox ) );
    } );

    $( document ).on( 'click', 'a.library-chooser', function () {
      openLibrary();
    } );

    $( '.lightbox-close' ).on( 'click', function () {
      toggleLightBox( $( this ) );
    } );

    addMetaBoxEventListeners();
    addLibraryEventListeners();

    /**
     * Handle the close of the "Link" button.  Strips off the <a> tag and text, and simply returns the URL
     */
    $( document ).on( 'wplink-close', function ( event ) {
      var $link = $( event.currentTarget.activeElement );
      var link  = $link.val();

      if ( !link ) {
        link = $link.data( 'old-link' );
      }

      var match = /<a\s+(?:[^>]*?\s+)?href="([^"]*)"/g.exec( link );

      if ( match && match[ 1 ] ) {
        link = match[ 1 ];
      }

      $link.val( link );
    } );

    $( window ).on( 'YoastSEO:ready', setupYoast );

    // "Additional classes" effects
    $( '.additional-classes input' ).each( function () {
      dressClasses( $( this ) );
    } );

    $sectionNavigator.on( 'change', function () {
      var id = $( this ).val();

      if ( !id ) {
        return;
      }

      $( 'html, body' ).scrollTop( $( '#' + id ).offset().top - 50 );
    } );

    $( window ).on( 'load scroll', function () {
      if ( $( window ).scrollTop() > 150 ) {
        $adminNav.removeClass( 'at-top' );
      } else {
        $adminNav.addClass( 'at-top' );
      }
    } );
  }

  function isSectionCollapsed( $icon ) {
    return $icon.closest( '.section' ).hasClass( 'collapsed' );
  }

  function addMetaBoxEventListeners() {
    $sectionLightBox.on( 'click', '.add-section', function () {
      var section   = $( this ).data( 'section' );
      $openLightBox = $sectionLightBox;
      getSection( section );
    } );

    $( '.chooser-lightbox .search' ).on( 'keyup', function () {
      searchSections( $( this ) );
    } );

    $metaBoxSections.on( 'click', '.add-sub-content', function () {
      getSubContent( $( this ).closest( '.section' ) );
    } );

    $metaBoxSections.on( 'click', '.collapse-section', function () {
      var $icon       = $( this ).find( '.fa' );
      var $body       = $( this ).closest( '.section' ).find( '.section-body' );
      var isCollapsed = isSectionCollapsed( $icon ); // $icon.hasClass( 'fa-expand' );

      if ( !isCollapsed ) {
        $body.slideUp( 'fast' ).closest( '.section' ).addClass( 'collapsed' );
        $icon.addClass( 'fa-expand' ).removeClass( 'fa-compress' ).attr( 'title', 'Expand Section' );
      } else {
        $body.slideDown( 'fast' ).closest( '.section' ).removeClass( 'collapsed' );
        $icon.addClass( 'fa-compress' ).removeClass( 'fa-expand' ).attr( 'title', 'Collapse Section' );
      }

      updateSectionState();
    } );

    $metaBoxSections.on( 'click', '.delete-sub-content', function () {
      if ( !confirm( 'Are you sure you want to remove this item?' ) ) {
        return;
      }

      $( this ).closest( 'div' ).remove();
      reIndexSections();
    } );

    $metaBoxSections.on( 'click', '.delete-section', function () {
      if ( !confirm( 'Are you sure you want to remove this section?' ) ) {
        return;
      }

      $( this ).closest( '.section' ).remove();
      checkIfEmpty();
      reIndexSections();
    } );

    $metaBoxSections.on( 'click', '.section-wplink', function () {
      var id = $( this ).data( 'wplink-id' );
      $( '#' + id ).data( 'old-link', $( this ).val() ).text( '' );
      wpLink.open( id );
    } );

    // open the class picker lightbox
    $metaBoxSections.on( 'click', '.additional-classes a', function () {
      $class_input = $( this ).closest( '.additional-classes' ).find( 'input' );

      updateClasses();
      toggleLightBox( $( '.lightbox-close', $classLightBox ) );
    } );

    // choose / toggle classes in the class picker lightbox
    $classLightBox.on( 'click', '.add-class', function () {
      updateClasses( $( this ) );
    } );

    // click into the classes "pills", transform to an input for direct entry
    $metaBoxSections.on( 'click', '.pills', function () {
      $( this ).hide();
      var $input = $( this ).closest( '.additional-classes' ).find( 'input' );
      var val    = $input.val();
      // this puts the cursor at the end of the input
      $input.show().focus().val( '' ).val( val );
    } );

    // delete a class from the pills display
    $metaBoxSections.on( 'click', '.pills span', function ( e ) {
      e.stopPropagation();

      $( this ).data( 'class', $( this ).text() );
      $class_input = $( this ).closest( '.additional-classes' ).find( 'input' );
      updateClasses( $( this ) );
    } );

    // click off the classes input, dress it to pills
    $metaBoxSections.on( 'blur', '.additional-classes input', function () {
      dressClasses( $( this ) );
    } );

    $metaBoxSections.on( 'change', '.recent-post-type', function () {
      handleRecentPostType( $( this ) );
    } );

    $metaBoxSections.find( '.recent-post-type' ).each( function () {
      handleRecentPostType( $( this ) );
      // TODO - this could be done better
      bindSearchable( $( this ).siblings( '.searchable' ) );
    } );

    // on entry, set titles in the section headings for easier distinction between titles
    $metaBoxSections.on( 'keyup', '.is-title', function () {
      setPanelTitle( $( this ) );
    } );

    // set the titles on initial load
    $metaBoxSections.find( '.is-title' ).each( function () {
      setPanelTitle( $( this ) );
    } );
  }

  function addLibraryEventListeners() {
    // click the bookmark to save a section to the Library
    $metaBoxSections.on( 'click', '.save-section', function () {
      var $title = $( this ).closest( '.section-title' ).clone();
      $title.find( 'a' ).remove();
      $title.find( 'label' ).remove();
      $( '.section-title', $saveLibraryLightBox ).html( $title.html() );
      $saveLibraryLightBox.data( 'index', $( this ).closest( 'div.section' ).index() );
      loadLibrary( $saveLibraryLightBox );
      toggleLightBox( $( '.lightbox-close', $saveLibraryLightBox ) );
    } );

    // click the "Save to Library" button in the lightbox
    $saveLibraryLightBox.on( 'click', '.save-library', function () {
      var index       = $saveLibraryLightBox.data( 'index' );
      var name        = $( 'input[name="section_name"]', $saveLibraryLightBox ).val();
      var description = $( '.section-title', $saveLibraryLightBox ).html();

      var form = $( 'form#post' ).serialize();
      //var regex   = new RegExp( "^section\\[" + index + "\\]" );
      //var section = $.grep( form, function ( val ) {
      //  return val.name.match( regex );
      //} );

      form += '&name=' + name;
      form += '&description=' + description;
      form += '&index=' + index;
      form += '&action=mosaic-save-library';

      var $el = $( this );

      $.ajax( ajaxurl, {
        method: 'POST',
        data: form,
        //data: {
        //  action: 'mosaic-save-library',
        //  section: form,
        //  name: name,
        //  description: description,
        //  index: index
        //},
        success: function () {
          $el.closest( '.chooser-lightbox' ).find( '.library', $el ).html( '<p class="mosaic-alert mosaic-success">Section Saved.</p>' );

          setTimeout( function () {
            toggleLightBox( $el );
          }, 1500 );
        }
      } );
    } );

    // click to "Delete Item" from the library
    // NOTE: two explicit click event handlers required to properly prevent event propagation
    $libraryLightBox.on( 'click', '.delete-library', function ( event ) {
      event.stopPropagation();
      removeLibrary( $( this ) );
    } );

    // click to "Delete Item" from the library
    // NOTE: two explicit click event handlers required to properly prevent event propagation
    $saveLibraryLightBox.on( 'click', '.delete-library', function ( event ) {
      event.stopPropagation();
      removeLibrary( $( this ) );
    } );

    // click the "Choose Section" in the Library Chooser
    $libraryLightBox.on( 'click', '[data-library-index]', function () {
      var index   = $( this ).data( 'library-index' );
      var section = library[ index ];

      $openLightBox = $libraryLightBox;
      getSection( section.data.type, section );
    } );
  }

  /**
   * Hide the main content editor, show the "Placeholder" if appropriate.
   */
  function setupInterface() {
    if ( !($( document ).find( '#mosaic-home-sections' ).length > 0) ) {
      return;
    }

    $( '#postdivrich' ).hide();
    $metaBoxSections.closest( '.postbox' ).prepend( '<a href="javascript:void(0);" class="button collapse-sections-all"><i class="fa fa-compress"></i> <span>Collapse</span> All Sections</a>' );
    checkIfEmpty();
  }

  function checkIfEmpty() {
    if ( $metaBoxSections.is( ':empty' ) ) {
      $metaBoxSections.append( '<div class="empty-placeholder"><h3>Add your first section!</h3>Click the "Add Section" button below, then choose the type of section.</div>' );
    }

    ensureAddSectionButton();
  }

  function ensureAddSectionButton() {
    if ( $( 'div.add-section', $metaBoxSections ).length ) {
      // moves the "add section" to the end.
      $( 'div.add-section', $metaBoxSections ).appendTo( $metaBoxSections );
    } else {
      $metaBoxSections.append( '<div class="add-section"><a class="button button-default section-chooser">Add Section</a></div>' );
    }
  }

  function toggleLightBox( $el ) {
    $el.closest( '.chooser-lightbox' ).toggleClass( 'choose' );
  }

  /**
   * Makes AJAX call to load the section contents, based on the type of section.
   *
   * @param {string} section
   * @param {array} [data]
   */
  function getSection( section, data ) {
    var section_id = $( '.section', $metaBoxSections ).length;

    $.ajax( ajaxurl, {
      method: 'POST',
      data: {
        action: 'mosaic-template-ajax',
        section: section,
        section_id: section_id,
        section_data: data
      },
      success: renderSection
    } );
  }

  /**
   * Makes AJAX call to load the bucket content
   *
   * @param {obj} $section
   */
  function getSubContent( $section ) {
    var section_id   = $section.data( 'section-id' );
    var section_type = $section.data( 'section-type' );
    var child_id     = $( '.sub-contents > *', $section ).length; ///* immediate list of sub content

    $.ajax( ajaxurl, {
      method: 'POST',
      data: {
        action: 'mosaic-template-ajax',
        section_id: section_id,
        child_id: child_id,
        sub_type: section_type
      },
      success: renderSubContent
    } );
  }

  /**
   * Renders the new interface "section" based on the AJAX response.
   *
   * @param {json} response
   */
  function renderSection( response ) {
    $( '.empty-placeholder', $metaBoxSections ).remove();
    $metaBoxSections.append( '<div class="section" data-section-type="' + response.type + '"><div class="section-title">' + response.title + '<span class="content-title"></span><a class="delete-section"><span class="dashicons dashicons-no"></span></a><a class="collapse-section" title="Collapse Section"><span class="fa fa-compress"></span></a><a class="save-section"><span class="fa fa-bookmark-o"></span></a><label class="hide-section">Hide <input type="checkbox" name="section[0][hide]"></label></div><div class="section-body">' + response.html + '</div></div>' );
    ensureAddSectionButton();

    if ( response.editors ) {
      $.each( response.editors, function ( i, editor_id ) {
        initTinyMCE( editor_id );
      } );
    }

    bindSortable();
    reIndexSections();

    var $last = $metaBoxSections.find( '.section' ).last();
    var top   = $last.position().top;
    $( 'html, body' ).animate( { scrollTop: top }, 500 );

    toggleLightBox( $( '.lightbox-close', $openLightBox ) );
  }

  /**
   * Renders the new interface "content_slider" based on AJAX response.
   * @param {json}  response
   */
  function renderSubContent( response ) {
    var section_id = response.section_id;
    $( '.section:eq(' + section_id + ')', $metaBoxSections ).find( '.sub-contents' ).append( response.html );

    bindSortable();
    reIndexSections();
  }

  /**
   * Spawn the Library Chooser lightbox, trigger the AJAX load of library sections.
   */
  function openLibrary() {
    toggleLightBox( $( '.lightbox-close', $libraryLightBox ) );
    loadLibrary( $libraryLightBox );
  }

  /**
   * Make AJAX request to load the library sections.
   *
   * @param {jQuery} $el
   */
  function loadLibrary( $el ) {
    $( '.library', $el ).html( '<p class="loading"><i class="fa fa-refresh fa-spin"></i> Loading...</p>' );
    $.ajax( ajaxurl, {
      method: 'POST',
      data: {
        action: 'mosaic-load-library'
      },
      success: function ( response ) {
        renderLibrary( response, $el );
      }
    } );
  }

  /**
   * Draws the Library Sections into the specified lightbox.
   *
   * @param {*} response
   * @param {jQuery} $el
   */
  function renderLibrary( response, $el ) {
    $( '.library', $el ).html( '' );
    var data = $.parseJSON( response );
    if ( !data || !data.length ) {
      $( '.library', $el ).append( '<p class="mosaic-alert mosaic-warning">No Sections Saved.... yet!</p>' );
    }

    library = data;

    $.each( data, function ( index, row ) {
      var $row = $( '<div class="item library-item" data-library-index="' + index + '"></div>' );
      $row.append( '<div class="name">' + row.name + '</div>' );
      $row.append( '<div class="description">' + row.description + '</div>' );
      $row.append( '<a class="delete delete-library" href="javascript:void(0);"><span class="fa fa-times"></span></a>' );
      $( '.library', $el ).append( $row );
    } );
  }

  function removeLibrary( $el ) {
    var index   = $el.closest( '.library-item' ).data( 'library-index' );
    var section = library[ index ];

    $el.closest( '.library' ).html( '<p class="loading"><i class="fa fa-refresh fa-spin"></i> Deleting...</p>' );

    $.ajax( ajaxurl, {
      method: 'POST',
      data: {
        action: 'mosaic-delete-library',
        section: section,
        index: index
      },
      success: function () {
        loadLibrary( $openLightBox );
      }
    } );
  }

  function bindSortable() {
    $metaBoxSections.sortable( {
      handle: '.section-title',
      opacity: .6,
      placeholder: 'sortable-placeholder',
      beforeStart: startSort,
      stop: stopSort
    } );

    $( '.sub-contents.sortable' ).sortable( {
      handle: '.sub-content-title',
      opacity: .6,
      placeholder: 'sortable-placeholder',
      beforeStart: function ( event, ui ) {
        //startSort( event, ui );
      },
      stop: function ( event, ui ) {
        reIndexSections();
      }
    } );
  }

  /**
   * Before sorting:
   * Set max-height limitation on sortable elements to make sorting more reasonable
   * Set min-height on container, so user doesn't get "lost" if dragging from the bottom of the list.
   * Remove TinyMCE on editor elements if they exist
   *
   * @param event
   * @param ui
   */
  function startSort( event, ui ) {
    $metaBoxSections.css( { minHeight: $metaBoxSections.outerHeight() } );
    $( '.section-body', $metaBoxSections ).css( { maxHeight: 200, overflow: 'hidden' } );

    sortedEditors = $( ui.item ).find( 'textarea' );

    sortedEditors.each( function () {
      // Only the WYSIWYG editors should have IDs
      var sortedEditorID = $( this ).attr( 'id' );

      if ( sortedEditorID ) {
        try {
          tinyMCE.execCommand( 'mceRemoveEditor', false, sortedEditorID )
        } catch ( e ) {
        }
      }
    } );
  }

  /**
   * After sorting:
   * Unset max-height limitation on sortable elements
   * Unset min-height on section container
   * Re-enable TinyMCE editors if they exist
   * Update sort order of Sections
   *
   * @param event
   * @param ui
   */
  function stopSort( event, ui ) {
    $metaBoxSections.css( { minHeight: 'none' } );
    $( '.section-body', $metaBoxSections ).css( { maxHeight: 'none', overflow: 'visible' } );

    sortedEditors.each( function () {
      // Only the WYSIWYG editors should have IDs
      var sortedEditorID = $( this ).attr( 'id' );

      if ( sortedEditorID ) {
        try {
          tinyMCE.execCommand( 'mceAddEditor', false, sortedEditorID )
        } catch ( e ) {
        }
      }
    } );

    reIndexSections();
  }

  /**
   * Update sorting of the sections.
   * Re-index the numeric "section" keys.
   */
  function reIndexSections() {
    var index = 0;
    var name;

    $( '.section', $metaBoxSections ).each( function () {
      $( '[name^="section\["]', this ).each( function () {
        name = $( this ).attr( 'name' );
        // Names are in format "section[23][button_url]", etc.
        // We want to replace the NUMBER only
        name = name.replace( /section\[\d+/, index );
        name = 'section[' + name;
        $( this ).attr( 'name', name );
      } );

      $( this ).data( 'section-id', index );
      index++;
    } );

    // now reindex the sub-items in each section
    $( '.section', $metaBoxSections ).each( function () {
      index = 0;

      $( '.sub-content', this ).each( function () {
        $( '[name^="section\["]', this ).each( function () {
          name = updateSubContentIndex( $( this ).attr( 'name' ), index );
          $( this ).attr( 'name', name );
        } );

        index++;
      } );
    } );

    populateSectionNavigator();
  }

  function updateSubContentIndex( inputName, newIndex ) {
    var m;
    var matches = [];

    while ( (m = subContentRegex.exec( inputName )) !== null ) {
      // This is necessary to avoid infinite loops with zero-width matches
      if ( m.index === subContentRegex.lastIndex ) {
        subContentRegex.lastIndex++;
      }

      // The result can be accessed through the `m`-variable.
      _.forEach( m, function ( s ) {
        matches.push( s );
      } );
    }

    return matches[ 1 ] + newIndex + matches[ 3 ];
  }

  /**
   * In 'Section Chooser Lightbox', Searches for section based on user input
   */
  function searchSections( $context ) {
    $context = $context.closest( '.chooser-lightbox' );

    var words = $( '.search', $context ).val();
    words     = words.toLowerCase();

    if ( !words ) {
      $( '.item', $context ).show();

      return;
    }

    words = words.split( ' ' );

    $( '.item', $context ).hide().each( function () {
        var flag = true;
        var text = $( this ).text().toLowerCase();

        $.each( words, function () {
          if ( text.indexOf( this ) < 0 ) {
            flag = false;
          }
        } );

        if ( flag ) {
          $( this ).show();
        }
      }
    );
  }

  function updateClasses( $el ) {
    var klass   = ($el && $el.length) ? $el.data( 'class' ) : '';
    var classes = $class_input.val();
    classes     = classes.replace( / {2,}/g, ' ' );
    classes     = classes.split( ' ' );
    $( '.item', $classLightBox ).removeClass( 'selected' );

    var remove = false;

    classes.forEach( function ( c, index ) {
      c = c.trim();

      if ( klass === c ) {
        remove = true;
        classes.splice( index, 1 );
      }
    } );

    if ( !remove ) {
      classes.push( klass );
    }

    classes.forEach( function ( c ) {
      $( '[data-class="' + c + '"]', $classLightBox ).addClass( 'selected' );
    } );

    classes = classes.join( ' ' );
    classes = classes.replace( / {2,}/g, ' ' );

    $class_input.val( classes );

    dressClasses( $class_input );
  }

  function dressClasses( $input ) {
    var $p = $input.closest( '.additional-classes' );

    if ( !$( '.pills', $p ).length ) {
      $input.before( '<span class="pills"></span>' );
    }

    var $pills = $( '.pills', $p );
    $pills.html( '' ).show();

    var classes = $input.val();
    classes     = classes.replace( / {2,}/g, ' ' );
    classes     = classes.split( ' ' );

    if ( classes.length ) {
      classes.forEach( function ( c ) {
        if ( c ) {
          $pills.append( '<span>' + c + '</span>' );
        }
      } );
    }

    $input.css( 'display', 'none' );
  }

  function setPanelTitle( $el ) {
    var val = $el.val();

    if ( $el.hasClass( 'partial-title' ) ) {
      // repair any unclosed html tags
      var div = $( '<div>' );
      div.html( val );
      val = div.text();
      val = val.substring( 0, 25 );
    }

    $el.closest( '.section' ).find( '.section-title' ).find( '.content-title' ).html( val );
  }

  function populateSectionNavigator() {
    $( 'options', $sectionNavigator ).remove();
    $sectionNavigator.append( '<option value="">Select Section...</option>' );

    $( '.section', $metaBoxSections ).each( function () {
      var id            = $( this ).attr( 'id' );
      var title         = $( this ).find( '.section-title' ).text();
      var content_title = $( this ).find( '.section-title .content-title' ).text();
      var $option       = $( '<option>' );

      title = title.replace( content_title, '' );
      title = title + ' - ' + content_title;

      $option.val( id );
      $option.text( title );

      $sectionNavigator.append( $option );
    } );
  }

  function handleRecentPostType( $el ) {
    var val      = $el.val();
    var $section = $el.closest( '.section' );
    var $chooser = $section.find( '.post-chooser' );
    var $newest  = $section.find( '.post-newest' );

    if ( 'manual' === val ) {
      $chooser.slideDown();
      $newest.slideUp();
    } else {
      $chooser.slideUp();
      $newest.slideDown();
    }
  }

  function bindSearchable( $el ) {
    // the containing section, for fast reference
    var $section    = $el.closest( '.section' );
    // the "search" input box
    var $search     = $section.find( '.searchable-search' );
    // the hidden input that stores the selected items
    var $input      = $section.find( '.searchable-ids' );
    // the display interface (sortable )
    var $display    = $section.find( '.searchable-selected' );
    // the data-source attribute, identifies which key on MosaicHome the data is on
    var source      = $el.data( 'source' );
    // the data-id attribute, identifies a records "ID" property
    var id_key      = $el.data( 'id' );
    // the data-search attribute, identifies which property is "searched"
    var search_key  = $el.data( 'search' );
    // the data-display attribute, identifies which property is displayed in results
    var display_key = $el.data( 'display' );

    // add the "search results" dropdown dressing
    $search.after( '<div class="search-results-wrap"><div class="search-results"></div></div>' );
    var $results = $section.find( '.search-results' );
    $results.hide();

    // initial setup of sortable
    if ( $input.val() ) {
      var ids = $input.val();
      ids     = ids.split( ',' );

      $.each( ids, function ( i, id ) {
        id = +id;

        var match = _.find( MosaicHome[ source ], function ( obj ) {
          return (+obj[ id_key ] === id);
        } );

        if ( match ) {
          var text = match[ display_key ];
          $display.append( '<p data-id="' + id + '">' + text + '</p>' );
        }
      } );

      bindSearchSortable( $display, $input );
    }

    $search.on( 'keyup', function () {
      var words = $( this ).val();
      if ( !words ) {
        $results.html( '' ).hide();
        return;
      }

      words = words.toLowerCase().split( ' ' );
      words = _.filter( words, function ( word ) {
        return !!word;
      } );

      var matches = _.filter( MosaicHome[ source ], function ( obj ) {
        var string = obj[ search_key ];
        if ( !string ) {
          return;
        }

        string    = string.toLowerCase();
        var match = true;

        _.each( words, function ( word ) {
          if ( string.indexOf( word ) < 0 ) {
            match = false;
            return false;
          }
        } );

        return match;
      } );

      $results.show();

      if ( !matches || !matches.length ) {
        $results.html( '<p class="tip">No results</p>' );
        return;
      }

      if ( matches.length > 20 ) {
        $results.html( '<p class="tip">Refine Search... too many matches</p>' );
        return;
      }

      $results.html( '' );
      _.each( matches, function ( match ) {
        $results.append( '<p data-id="' + match[ id_key ] + '">' + match[ display_key ] + '</p>' );
      } );
    } );

    $results.on( 'click', 'p', function () {
      $results.html( '' ).hide();

      var $this = $( this );
      var id    = $this.data( 'id' );
      var text  = $this.text();
      var ids   = $input.val();

      if ( ids ) {
        ids = ids.split( ',' );
        // coerce to integers
        ids = _.map( ids, function ( id ) {
          return +id;
        } );
      } else {
        ids = [];
      }

      // prevent duplicates
      if ( _.contains( ids, id ) ) {
        return;
      }

      ids.push( id );
      ids = ids.join( ',' );
      $input.val( ids );

      $display.append( '<p data-id="' + id + '">' + text + '</p>' );

      bindSearchSortable( $display, $input );
    } );

    $display.on( 'click', '.remove', function () {
      $( this ).parent().fadeOut( 'fast', function () {
        $( this ).remove();
        updateSearchSortable( $display, $input );
        bindSearchSortable( $display, $input );
      } );
    } );
  }

  function updateSearchSortable( $display, $input ) {
    ids = [];
    $display.find( 'p' ).each( function () {
      ids.push( $( this ).data( 'id' ) );
    } );

    $input.val( ids.join( ',' ) );
  }

  function bindSearchSortable( $display, $input ) {
    $display.sortable( {
      forcePlaceholderSize: true,
      update: function ( event, ui ) {
        updateSearchSortable( $display, $input );
      }
    } );

    $( 'a.remove', $display ).remove();
    $display.find( 'p' ).append( '<a class="remove"><i class="fa fa-times"></i></a>' );
  }

  function updateSectionState() {
    var states = [];

    $( '.section', $metaBoxSections ).each( function () {
      states.push( { id: $( this ).attr( 'id' ), collapsed: $( this ).hasClass( 'collapsed' ) } );
    } );

    var key = getSessionKey( 'mosaic-sections' );

    var state = { collapsed: states };
    state     = JSON.stringify( state );

    sessionStorage.setItem( key, state );
  }

  function loadSectionState() {
    var key   = getSessionKey( 'mosaic-sections' );
    var state = sessionStorage.getItem( key );

    state = $.parseJSON( state );

    if ( state && state.collapsed ) {
      $.each( state.collapsed, function ( index, section ) {
        if ( section.collapsed ) {
          var $section = $( '#' + section.id, $metaBoxSections );
          $section.addClass( 'collapsed' ).find( '.section-body' ).hide();
          $section.find( '.collapse-section .fa' ).removeClass( 'fa-compress' ).addClass( 'fa-expand' );
        }
      } );
    }
  }

  function getSessionKey( key ) {
    var user_id = $( '#user-id' ).val();
    var post_id = $( '#post_ID' ).val();

    return key + '-' + user_id + '-' + post_id;
  }

  /**
   * Integrate with YoastSEO so that custom "section" content appears in the SEO analysis.
   *
   * @help https://github.com/Yoast/YoastSEO.js/blob/develop/docs/Customization.md
   * @help https://return-true.com/adding-content-to-yoast-seo-analysis-using-yoastseojs/
   */
  function setupYoast() {
    YoastSEO.app.registerPlugin( yoastPluginName, { status: 'ready' } );
    YoastSEO.app.registerModification( 'content', MosaicHomeClass.getContents, yoastPluginName, 5 );

    $metaBoxSections.on( 'keyup blur', contentSelector, function () {
      clearTimeout( debounce.Yoast );
      debounce.Yoast = setTimeout( function () {
        YoastSEO.app.pluginReloaded( yoastPluginName );
      }, 500 );
    } );
  }

  /**
   * When adding an editor (via wp_editor()) via AJAX, need to re-bind TinyMCE
   * to make it work properly.
   *
   * @param {string} editor_id
   */
  function initTinyMCE( editor_id ) {
    var tinyMCESettings = tinyMCEPreInit.mceInit.content;

    tinyMCESettings.setup = function ( editor ) {
      editor.on( 'change', function () {
        tinymce.triggerSave();
      } );
    };

    tinymce.init( tinyMCESettings );
    tinyMCE.execCommand( 'mceAddEditor', false, editor_id );
    quicktags( { id: editor_id } );
  }

  // public functions here
  return {
    init: function () {
      $adminNav            = $( '#mosaic-admin-nav' );
      $sectionNavigator    = $( '#mosaic-section-jump' );
      $metaBoxControls     = $( '#mosaic-home-template-controls .inside' );
      $metaBoxSections     = $( '#mosaic-home-sections' );
      $sectionLightBox     = $( '#section-chooser-lightbox' );
      $classLightBox       = $( '#section-class-chooser-lightbox' );
      $libraryLightBox     = $( '#section-library-lightbox' );
      $saveLibraryLightBox = $( '#section-library-save-lightbox' );
      $sectionSearch       = $( '.section-search', $sectionLightBox );
      $classSearch         = $( '.class-search', $classLightBox );
      $bucketWrapper       = $( '.bucket-wrapper' );

      // Create a "beforeStart" event for sortables
      var oldMouseStart                   = $.ui.sortable.prototype._mouseStart;
      $.ui.sortable.prototype._mouseStart = function ( event, overrideHandle, noActivation ) {
        this._trigger( "beforeStart", event, this._uiHash() );
        oldMouseStart.apply( this, [ event, overrideHandle, noActivation ] );
      };

      bindEvents();
      bindSortable();
      setupInterface();
      reIndexSections();
      loadSectionState();

      MosaicColorChooser.init( this.applyColors );
      this.applyColors();
    },

    applyColors: function () {
      $( 'div.section', $metaBoxSections ).each( function () {
        var $section      = $( this );
        var $sectionTitle = $( 'div.section-title', $section );
        var color         = $section.find( '.color-scheme-wrapper.section-chooser [name*="\[color\]"]' ).val();

        if ( MosaicHome.colorSchemes.hasOwnProperty( color ) ) {
          color      = MosaicHome.colorSchemes[ color ];
          var bg     = (color.hasOwnProperty( 'background' )) ? color.background : '#fff';
          color      = (color.hasOwnProperty( 'background' )) ? color.color : '#000';
          var border = (bg === '#fff' || bg === '#ffffff') ? color : bg;
          $sectionTitle.css( { background: bg, color: color, borderColor: border } );
          $section.css( { borderColor: border } );
        }

        // For deselecting color schemes
        if ( !color ) {
          $sectionTitle.css( { background: "", color: "", borderColor: "" } );
          $section.css( { borderColor: "" } )
        }

        var subSchemes = $section.find( '.color-scheme-wrapper.split-scheme-chooser' );

        if ( subSchemes.length > 0 ) {
          subSchemes.each( function () {
            var color = $( this ).find( '[name*="\[color\]"]' ).val();

            if ( MosaicHome.colorSchemes.hasOwnProperty( color ) ) {
              color      = MosaicHome.colorSchemes[ color ];
              var bg     = (color.hasOwnProperty( 'background' )) ? color.background : '#fff';
              color      = (color.hasOwnProperty( 'background' )) ? color.color : '#000';
              var border = (bg === '#fff' || bg === '#ffffff') ? color : bg;
              $( this ).siblings( '.title' ).css( { background: bg, color: color, borderColor: border } );
              $( this ).closest( '.split-container' ).css( { borderColor: border } );
            }
          } );
        }
      } );
    },

    getContents: function ( data ) {
      var text   = '';
      var images = '';

      $( contentSelector, $metaBoxSections ).each(
        function () {
          var type = ($( this ).attr( 'name' ).indexOf( 'headline' ) > 0) ? 'h3' : 'p';
          type     = ($( this ).attr( 'name' ).indexOf( '_title' ) > 0) ? 'h2' : type;
          text += '<' + type + '>' + $( this ).val() + '</' + type + '>';
        }
      );

      $( imageSelector, $metaBoxSections ).each(
        function () {
          if ( $( this ).val() ) {
            var caption = $( this ).closest( 'div.sub-contents' ).find( 'input[type="text"][name*="caption"]' ).val();
            images += '<img src="' + $( this ).val() + '" alt="' + caption + '">';
          }
        }
      );

      return text + images;
    }
  }
})( jQuery );

