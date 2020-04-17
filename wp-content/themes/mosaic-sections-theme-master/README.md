# Mosaic Sections Theme
## Highly Extensible "builder"-like theme.

### Adding a Custom Section:
1. Add the section to the buttons using the filter `mosaic_register_sections`

```
public function mosaic_register_sections( $sections ) {
	$sections[] = [
        'name'           => 'quote',
        'button_text'    => 'Quote + Image',
        'button_icon'    => 'fa-quote-left',
        'admin_section'  => [ $this, 'quote_admin_section' ],
        'render_section' => [ $this, 'quote_render_section' ]
	];

	return $sections;
}
```
