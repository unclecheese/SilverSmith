<?php

class DateField_Config extends SilverSmithFieldConfig {

	public function getDBField() {
		return "Date";
	}



	public function getAliases() {
		return array (
			'Date', 
			'DatePicker', 
			'CalendarDateField', 
			'Calendar', 
			'DatePickerField'
		);
	}



	

	
}