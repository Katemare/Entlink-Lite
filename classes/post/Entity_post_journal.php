<?
class Entity_post_journal extends Entity_post
{
	static $mod_model=array(
		'title'=>array(
			'table'=>'info_activity',
			'field'=>'title'
		),
		'contibutor'=>array(
			'table'=>'info_activity',
			'field'=>'userID'
		),
		'approved'=>array(
			'default'=>'approved'
		),
		'date_contibuted'=>array(
			'table'=>'info_activity',
			'field'=>'added'
		),
		'date_published'=>array(
			'table'=>'info_activity',
			'field'=>'added'
		),
		'date_bumped'=>array(
			'table'=>'info_activity',
			'field'=>'added'
		),
		'content'=>array(
			'type'=>'text',
			'table'=>'info_activity',
			'field'=>'usertext'
		)
	);
	
	public function setModel()
	{
		parent::setModel();
		if (count(self::$mod_model)) $this->mergeModel(self::$mod_model);
	}
}
?>