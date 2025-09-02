<?php

namespace SIWP\WPT;

use StoutLogic\AcfBuilder\FieldsBuilder;

class Theme_ACF extends FieldsBuilder
{
	public function build_fields()
	{
		// Check if ACF has already been initialized
		if (did_action('acf/init')) {
			// ACF is ready, register immediately
			acf_add_local_field_group($this->build());
		} else {
			// ACF not ready yet, wait for init
			add_action('acf/init', function () {
				acf_add_local_field_group($this->build());
			});
		}
 
		return $this;
	}

	public function builder()
	{
		// This method is now redundant, but keeping for backwards compatibility
		return $this->build_fields();
	}
}