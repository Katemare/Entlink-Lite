<?

class Entity_test extends Entity_combo
{
	public $model=array(
		'somestring'=>array(
			'type'=>'string',
			'table'=>'somedata',
			'field'=>'sometext'
		),
		'number'=>array(
			'type'=>'number',
			'table'=>'somedata',
			'field'=>'number'
		)
	);
	
	public static $mod_formats=array(
		'values_html'=>array('dic', 'test.basic_msg')
	);
	
	public function setFormats()
	{
		parent::setFormats();
		$this->formats=array_merge($this->formats, self::$mod_formats);
	}
}

?>