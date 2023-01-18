(function() {
    tinymce.PluginManager.add('shortcodes_button', function( editor, url ) {
        editor.addButton( 'shortcodes_button', {
            title: 'Add Elements',
            type: 'menubutton',
            icon: 'plus',
            menu: [
                {
                    text: 'Button',
                    onclick: function() {
                        editor.windowManager.open( {
				        title: 'Button',
				        icon: 'plus',
				        body: [{
				            type: 'listbox',
				            name: 'style',
				            label: 'Style',
				            'values': [
				                {text: 'Filled', value: 'primary'},
				                {text: 'Outline', value: 'secondary'}
				            ]
                        },
				        {
				            type: 'textbox',
				            name: 'text',
				            label: 'Text'
				        },
				        {
				            type: 'textbox',
				            name: 'link',
				            label: 'Link (URL)'
				        },
				        {
				            type: 'listbox',
				            name: 'target',
				            label: 'Link Target',
				            'values': [
				                {text: 'Open in same tab', value: '_self'},
				                {text: 'Open in new tab', value: '_blank'},
				            ]
				        }],
				        onsubmit: function( e ) {
				            editor.insertContent( '<a class="button ' + e.data.style + '" href="' + e.data.link + '" target="' + e.data.target + '">' + e.data.text + '</a>');
                        }
				    });
                    }
                },
                {
                    text: 'Checklist',
                    onclick: function() {
                        editor.windowManager.open( {
				        title: 'Checklist',
				        icon: 'plus',
				        body: [
							{
								type: 'listbox',
								name: 'columns',
								label: 'Columns',
								'values': [
									{text: 'One', value: '1'},
									{text: 'Two', value: '2'},
									{text: 'Three', value: '3'},
								]
							}
						],
				        onsubmit: function( e ) {
                            editor.insertContent( '<ul class="checklist" style="columns: ' + e.data.columns + '"><li>Your list item here</li></ul>');
                        }
				    });
                    }
                },
				{
                    text: 'Multi-Column List',
                    onclick: function() {
                        editor.windowManager.open( {
				        title: 'Multi-Column List',
				        icon: 'plus',
				        body: [
							{
								type: 'listbox',
								name: 'columns',
								label: 'Columns',
								'values': [
									{text: 'One', value: '1'},
									{text: 'Two', value: '2'},
									{text: 'Three', value: '3'},
								]
							},
							{
								type: 'listbox',
								name: 'style',
								label: 'List Style',
								'values': [
									{text: 'Unordered', value: 'ul'},
									{text: 'Ordered', value: 'ol'},
								]
							}
						],
				        onsubmit: function( e ) {
                            editor.insertContent( '<' + e.data.style + ' style="columns: ' + e.data.columns + '"><li>Your list item here</li></' + e.data.style + '>');
                        }
				    });
                    }
                },
        	]
        });
    });
})();