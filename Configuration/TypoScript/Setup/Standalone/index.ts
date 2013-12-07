plugin.tx_beautyofcode {
	settings {
		showLabel = {$plugin.tx_beautyofcode.settings.showLabel}

		library = {$plugin.tx_beautyofcode.settings.library}

		baseUrl = {$plugin.tx_beautyofcode.settings.baseUrl}
		scripts = {$plugin.tx_beautyofcode.settings.scripts}
		styles = {$plugin.tx_beautyofcode.settings.styles}
	
		# if enabled JS code is fired with a domReady event (not recomended)
		# always enabled if TYPO3 version < 4.3
		# possible options: false, native, jquery
		includeAsDomReady = {$plugin.tx_beautyofcode.settings.includeAsDomReady}
		onReadyCallback = {$plugin.tx_beautyofcode.settings.onReadyCallback}

		theme = {$plugin.tx_beautyofcode.settings.theme}
		brushes = {$plugin.tx_beautyofcode.settings.brushes}

		defaults {
			tab-size = {$plugin.tx_beautyofcode.settings.defaults.tab-size}
			gutter = {$plugin.tx_beautyofcode.settings.defaults.gutter}
			collapse = {$plugin.tx_beautyofcode.settings.defaults.collapse}
		}
	}

	_CSS_DEFAULT_STYLE (
		.tx_beautyofcode pre { overflow: auto; }
	)
}