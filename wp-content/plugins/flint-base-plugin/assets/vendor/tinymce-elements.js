(function() {
  tinymce.PluginManager.add('shortcodes_button', function(editor) {
    var elements = Array.isArray(window.flintWysiwygElements) ? window.flintWysiwygElements : [];
    editor.addButton('shortcodes_button', {
      title: 'Add Elements', type: 'menubutton', icon: 'plus',
      menu: elements.map(function(item) {
        return { text: item.label || 'Element', onclick: function() { editor.insertContent(item.html || ''); } };
      })
    });
  });
})();
